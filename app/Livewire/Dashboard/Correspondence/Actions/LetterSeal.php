<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class LetterSeal
{
    private const SIGNATURE_PATH = '#^users/signatures/[A-Za-z0-9._-]+\.(png|jpe?g|webp)$#i';

    private const SNAPSHOT_BYTES = 16384;

    public static function finalize(Correspondence $row, array &$copied): void
    {
        $head = Letterhead::query()->find($row->letterhead_id);
        if ($head === null || $head->kind !== Letterhead::KIND_HEAD) {
            throw LetterRules::fail('letterhead_id', 'invalid_value');
        }

        $parties = LetterFlow::parties($row);
        $wet = $row->sign_mode === Correspondence::SIGN_MODE_WET;
        $sources = $wet ? [] : self::signatureSources($parties);

        $moment = now('Asia/Tehran');
        $number = $head->nextNumber(LetterFlow::numberContext($row, $moment));

        if (!$wet) {
            $parties = self::stampSignatures($row, $parties, $sources, $copied);
        }

        $meta = (array) ($row->meta ?? []);
        unset($meta['resume']);
        $meta['snapshot'] = self::snapshotLetterhead($head, $row, $copied);

        $row->forceFill([
            'parties' => $parties,
            'number' => $number['number'],
            'number_seq' => $number['seq'],
            'letter_date' => LetterFlow::jalali($moment),
            'approved_at' => now(),
            'status' => Correspondence::STATUS_APPROVED,
            'awaiting_user_id' => null,
            'meta' => $meta,
        ]);
        $row->content_hash = self::frozenHash($row);
    }

    public static function signatureSources(array $parties): array
    {
        $approvers = array_filter($parties, fn (array $party) => ($party['role'] ?? null) === LetterForm::ROLE_APPROVER && filled($party['user_id'] ?? null));
        $ids = array_values(array_unique(array_map(fn (array $party) => (int) $party['user_id'], $approvers)));

        if ($ids === []) {
            return [];
        }

        $users = User::query()->whereIn('id', $ids)->get(['id', 'name', 'extra'])->keyBy('id');
        $disk = Storage::disk('local');
        $sources = [];
        $missing = [];

        foreach ($ids as $id) {
            $source = $users->get($id)?->getExtraValue('admin.signature');

            if (is_string($source) && preg_match(self::SIGNATURE_PATH, $source) === 1 && $disk->exists($source)) {
                $sources[$id] = $source;
            } else {
                $missing[] = (string) ($users->get($id)?->name ?? $id);
            }
        }

        if ($missing !== []) {
            throw LetterRules::fail('status', 'signature_missing', ['names' => implode('، ', $missing)]);
        }

        return $sources;
    }

    private static function stampSignatures(Correspondence $row, array $parties, array $sources, array &$copied): array
    {
        $disk = Storage::disk('local');

        foreach ($parties as $index => $party) {
            $source = $sources[(int) ($party['user_id'] ?? 0)] ?? null;

            if ($source === null || ($party['role'] ?? null) !== LetterForm::ROLE_APPROVER) {
                continue;
            }

            $dest = LetterRules::directory((int) $row->id) . '/signature-' . (int) $party['user_id'] . '.' . strtolower(pathinfo($source, PATHINFO_EXTENSION));
            $disk->copy($source, $dest);
            $copied[] = $dest;
            $parties[$index]['signature_path'] = $dest;
        }

        return $parties;
    }

    private static function snapshotLetterhead(Letterhead $head, Correspondence $row, array &$copied): array
    {
        $config = (array) ($head->config ?? []);
        $borrowed = self::borrowedHeads([$config['header_from'] ?? null, $config['footer_from'] ?? null]);
        $sources = [
            'header' => self::borrowedImage($borrowed, $config['header_from'] ?? null, 'header_image') ?? $head->header_image,
            'footer' => self::borrowedImage($borrowed, $config['footer_from'] ?? null, 'footer_image') ?? $head->footer_image,
            'stamp' => $head->stamp_image,
        ];

        $images = [];
        foreach ($sources as $slot => $path) {
            $images[$slot] = self::copyImage($path, $slot, $row, $copied);
        }

        $body = [
            'letterhead_id' => (int) $head->id,
            'name' => $head->name,
            'size' => $head->size,
            'lang' => $head->lang,
            'page_numbers' => (bool) $head->page_numbers,
            'watermark' => $head->watermark,
            'sign_layout' => $head->sign_layout,
            'margins' => $head->margins,
            'sender_block' => $head->sender_block,
            'style' => $head->style,
            'fields_box' => $head->fields_box,
            'config' => $config,
        ];
        $frame = [
            'images' => $images,
            'taken_at' => now()->toIso8601String(),
            'frame' => [
                ...LetterFlow::presenter()->typeFrame((string) $row->type),
                'counterparty' => config("ai.correspondence.types.{$row->type}.counterparty_signature") === true,
            ],
        ];

        if (strlen((string) json_encode($body, JSON_UNESCAPED_UNICODE)) <= self::SNAPSHOT_BYTES) {
            return $frame + $body;
        }

        $file = LetterRules::directory((int) $row->id) . '/letterhead.json';
        Storage::disk('local')->put($file, (string) json_encode($body, JSON_UNESCAPED_UNICODE));
        $copied[] = $file;

        return $frame + ['file' => $file];
    }

    private static function borrowedHeads(array $ids): Collection
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));

        return $ids === [] ? new Collection() : Letterhead::query()->whereIn('id', $ids)->get(['id', 'header_image', 'footer_image'])->keyBy('id');
    }

    private static function borrowedImage(Collection $heads, mixed $letterheadId, string $column): ?string
    {
        if (!is_numeric($letterheadId)) {
            return null;
        }

        $path = $heads->get((int) $letterheadId)?->{$column};

        return is_string($path) && $path !== '' ? $path : null;
    }

    private static function copyImage(mixed $path, string $slot, Correspondence $row, array &$copied): ?string
    {
        $public = Storage::disk('public');

        if (!is_string($path) || !str_starts_with($path, 'letterheads/') || str_contains($path, '..') || !$public->exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $stream = preg_match('/^[a-z0-9]{2,5}$/', $extension) === 1 ? $public->readStream($path) : null;
        if (!is_resource($stream)) {
            return null;
        }

        $dest = LetterRules::directory((int) $row->id) . "/letterhead-{$slot}.{$extension}";
        Storage::disk('local')->writeStream($dest, $stream);
        fclose($stream);
        $copied[] = $dest;

        return $dest;
    }

    private static function frozenHash(Correspondence $row): string
    {
        $hash = LetterRules::contentHash($row);
        $disk = Storage::disk('local');
        $files = [];

        foreach ([...($row->spec['attachments'] ?? []), ...($row->spec['blocks'] ?? [])] as $item) {
            $path = is_array($item) ? ($item['path'] ?? null) : null;

            if (is_string($path) && LetterRules::ownsPath($path, (int) $row->id)) {
                $files[] = $path . ':' . ($disk->exists($path) ? (string) hash_file('sha256', $disk->path($path)) : 'missing');
            }
        }

        return $files === [] ? $hash : hash('sha256', $hash . '|' . implode('|', $files));
    }
}
