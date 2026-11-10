<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Services\ContentSanitizerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LetterBody
{
    public static function spec(Correspondence $letter, array $payload, array $allowed, bool $structural): array
    {
        $id = (int) $letter->id;
        $current = $letter->spec ?? [];
        $spec = [
            'blocks' => self::blocks($payload['blocks'] ?? [], $allowed, $id),
            'attachments' => self::attachments($current['attachments'] ?? [], $allowed, $id),
        ];

        if (!$structural) {
            return $spec + array_intersect_key($current, array_flip(['recipient', 'sender']));
        }

        $spec['recipient'] = self::recipientBlock(is_array($payload['recipient'] ?? null) ? $payload['recipient'] : []);
        if ($letter->direction === Correspondence::DIRECTION_IN) {
            $spec['sender'] = self::senderBlock($payload['sender'] ?? '', is_array($current['sender'] ?? null) ? $current['sender'] : []);
        }

        return $spec;
    }

    public static function senderBlock(mixed $name, array $current): array
    {
        return [
            'name' => LetterRules::plain($name, 200),
            'organization' => LetterRules::plain($current['organization'] ?? '', 200),
            'address' => LetterRules::plain($current['address'] ?? '', 200),
        ];
    }

    public static function oneBlock(array $block, array $allowed, int $id): ?array
    {
        $block = array_intersect_key($block, array_flip(Correspondence::BLOCK_KEYS));
        $type = $block['type'] ?? null;

        if ($type === 'image') {
            $path = is_string($block['path'] ?? null) ? $block['path'] : '';

            return $path === '' || !self::pathAllowed($path, $allowed, $id)
                ? null
                : self::imageBlock($path, $block['size'] ?? null, $block['align'] ?? null, $block['caption'] ?? '');
        }

        $heading = LetterRules::plain($block['heading'] ?? '', 200);
        if ($type === 'text') {
            return ['type' => 'text', 'heading' => $heading, 'text' => is_string($block['text'] ?? null) ? strip_tags(mb_substr($block['text'], 0, 20000)) : ''];
        }

        $html = $block['html'] ?? $block['text'] ?? '';
        if (is_string($html) && strlen($html) > Correspondence::HTML_BLOCK_BYTES) {
            self::tooLarge();
        }

        return [
            'type' => 'html',
            'heading' => $heading,
            'html' => is_string($html) ? Correspondence::sanitizeHtml(ContentSanitizerService::clean($html)) : '',
        ];
    }

    public static function imageBlock(string $path, mixed $size, mixed $align, mixed $caption): array
    {
        return [
            'type' => 'image',
            'path' => $path,
            'size' => LetterRules::oneOf($size, Correspondence::BLOCK_SIZES, 'medium'),
            'align' => LetterRules::oneOf($align, Correspondence::BLOCK_ALIGNS, 'center'),
            'caption' => LetterRules::plain($caption, 200),
        ];
    }

    public static function knownPaths(Correspondence $letter): array
    {
        return self::specPaths($letter->spec ?? []);
    }

    public static function allPaths(Correspondence $letter): array
    {
        $paths = self::knownPaths($letter);
        foreach ($letter->versions ?? [] as $version) {
            if (is_array($version) && is_array($version['spec'] ?? null)) {
                $paths = [...$paths, ...self::specPaths($version['spec'])];
            }
        }

        return array_values(array_unique($paths));
    }

    public static function forgetUnused(Correspondence $letter, array $before): void
    {
        $id = (int) $letter->id;
        $unused = array_values(array_filter(
            array_diff($before, self::allPaths($letter)),
            fn (string $path) => LetterRules::ownsPath($path, $id)
        ));

        DB::afterCommit(fn () => Storage::disk('local')->delete($unused));
    }

    public static function liveSpec(array $spec): array
    {
        $disk = Storage::disk('local');
        $live = fn ($item) => !is_array($item) || !is_string($item['path'] ?? null) || $disk->exists($item['path']);

        foreach (['blocks', 'attachments'] as $key) {
            if (is_array($spec[$key] ?? null)) {
                $spec[$key] = array_values(array_filter($spec[$key], $live));
            }
        }

        return $spec;
    }

    private static function recipientBlock(array $recipient): array
    {
        return [
            'organization' => LetterRules::plain($recipient['organization'] ?? '', 200),
            'person' => LetterRules::plain($recipient['person'] ?? '', 200),
            'title' => LetterRules::plain($recipient['title'] ?? '', 200),
        ];
    }

    private static function blocks(mixed $blocks, array $allowed, int $id): array
    {
        $blocks = is_array($blocks) ? $blocks : [];
        if (count($blocks) > (int) config('ai.correspondence.max_blocks')) {
            self::tooLarge();
        }

        $clean = [];
        foreach ($blocks as $block) {
            if (is_array($block) && ($one = self::oneBlock($block, $allowed, $id)) !== null) {
                $clean[] = $one;
            }
        }

        return $clean === [] ? [LetterForm::BLANK_BLOCK] : $clean;
    }

    private static function attachments(mixed $files, array $allowed, int $id): array
    {
        $clean = [];
        foreach (is_array($files) ? $files : [] as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null) || !self::pathAllowed($file['path'], $allowed, $id)) {
                continue;
            }
            $clean[] = [
                'path' => $file['path'],
                'name' => LetterRules::plain($file['name'] ?? '', 200),
                'mime' => LetterRules::plain($file['mime'] ?? '', 100),
                'size' => max(0, (int) ($file['size'] ?? 0)),
            ];
        }

        return array_slice($clean, 0, LetterForm::limits()['files']);
    }

    private static function specPaths(array $spec): array
    {
        $paths = [];
        foreach ([...($spec['blocks'] ?? []), ...($spec['attachments'] ?? [])] as $file) {
            if (is_array($file) && is_string($file['path'] ?? null)) {
                $paths[] = $file['path'];
            }
        }

        return $paths;
    }

    private static function pathAllowed(string $path, array $allowed, int $id): bool
    {
        return in_array($path, $allowed, true) && LetterRules::ownsPath($path, $id);
    }

    private static function tooLarge(): never
    {
        throw ValidationException::withMessages(['spec' => __('resources/correspondence/strings.errors.body_too_large')]);
    }
}
