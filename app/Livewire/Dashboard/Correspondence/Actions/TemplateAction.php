<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class TemplateAction
{
    private const BLANK_STATUSES = [Correspondence::STATUS_DRAFT, Correspondence::STATUS_SAVED, Correspondence::STATUS_FAILED];

    private const REUSE_SECONDS = 5;

    public static function templatesFor(User $user): Builder
    {
        $department = $user->profile?->department_id;

        return Correspondence::query()->where('is_template', true)->where(function (Builder $query) use ($user, $department) {
            $query->where('user_id', $user->id);
            if (filled($department)) {
                $query->orWhere(fn (Builder $shared) => $shared
                    ->where('classification', Correspondence::CLASSIFICATION_NORMAL)
                    ->where('meta->template_scope', 'department')
                    ->where('meta->department', $department));
            }
        });
    }

    public static function isBlank(Correspondence $letter, ?User $user): bool
    {
        if (!$letter->isOwnedBy($user) || !in_array($letter->status, self::BLANK_STATUSES, true) || trim((string) $letter->subject) !== '') {
            return false;
        }

        foreach ($letter->spec['blocks'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'image' || stripHtml((string) ($block['html'] ?? $block['text'] ?? '')) !== '') {
                return false;
            }
        }

        return ($letter->spec['attachments'] ?? []) === [];
    }

    public function toggleTemplate(User $user, int $id, ?string $name = null, ?string $scope = null): void
    {
        DB::transaction(function () use ($user, $id, $name, $scope) {
            $fresh = Correspondence::query()->whereKey($id)->lockForUpdate()->first();
            if (!$fresh || !$fresh->isOwnedBy($user)) {
                LetterRules::deny();
            }

            $on = $name !== null || !$fresh->is_template;
            $meta = $fresh->meta ?? [];
            if ($on) {
                if (filled($name)) {
                    $meta['template_name'] = LetterRules::plain($name, 80);
                }
                $meta = $this->scoped($user, $meta, $scope ?? ($meta['template_scope'] ?? 'private'));
            }

            $fresh->forceFill(['is_template' => $on, 'meta' => $meta])->save();
        });
    }

    public function useTemplate(User $user, int $templateId, ?int $intoId = null): Correspondence
    {
        $key = "correspondence:template-use:{$user->id}:{$templateId}";
        if (!Cache::add($key, 0, self::REUSE_SECONDS)) {
            $recent = (int) Cache::get($key);
            $recent > 0 || LetterRules::deny();

            return Correspondence::query()->ownedBy($user->id)->whereKey($recent)->first() ?? LetterRules::deny();
        }

        try {
            $letter = $this->copy($user, $templateId, true, $intoId);
        } catch (Throwable $e) {
            Cache::forget($key);

            throw $e;
        }
        Cache::put($key, (int) $letter->id, self::REUSE_SECONDS);

        return $letter;
    }

    public function duplicate(User $user, int $sourceId): Correspondence
    {
        return $this->copy($user, $sourceId, false, null);
    }

    public function createFromTemplate(User $user, int $templateId, array $lines): array
    {
        $template = self::templatesFor($user)->whereKey($templateId)->first();
        $template || LetterRules::deny();
        $this->guardDirection($template, $user);

        $parsed = $this->parseLines($lines);
        $cap = LetterForm::limits()['batch'];
        if ($parsed === [] || count($parsed) > $cap) {
            throw ValidationException::withMessages([
                'batchLines' => __('resources/correspondence/strings.errors.batch_lines', ['max' => convertToPersian($cap)]),
            ]);
        }

        $base = $this->base($template);
        $parties = LetterParties::clean(array_filter($template->parties ?? [], fn ($party) => is_array($party) && filled($party['user_id'] ?? null)), []);
        $date = toJalali(now('Asia/Tehran'), 'Y/m/d');
        $ids = [];

        try {
            DB::transaction(function () use ($template, $user, $parsed, $base, $parties, $date, &$ids) {
                foreach ($parsed as $line) {
                    $merge = fn (string $text, bool $html) => $this->mergeFields($text, $line, $date, $html);
                    $this->seed($user, $template, new Correspondence(), $base, $parties, $merge, $line, $ids);
                }
                $template->touch();
            });
        } catch (Throwable $e) {
            $this->discard($ids);

            throw $e;
        }

        return $ids;
    }

    private function copy(User $user, int $sourceId, bool $template, ?int $intoId): Correspondence
    {
        $ids = [];

        try {
            return DB::transaction(function () use ($user, $sourceId, $template, $intoId, &$ids) {
                $source = ($template ? self::templatesFor($user) : Correspondence::query())->whereKey($sourceId)->lockForUpdate()->first();
                if (!$source || (!$template && !CorrespondenceAccessPolicy::canView($user, $source))) {
                    LetterRules::deny();
                }
                $this->guardDirection($source, $user);

                $target = $this->freshDraft($user, $intoId);
                $this->seed($user, $source, $target, $this->base($source), [], null, null, $ids);
                $template && $source->touch();

                return $target;
            });
        } catch (Throwable $e) {
            $this->discard($ids);

            throw $e;
        }
    }

    private function seed(User $user, Correspondence $source, Correspondence $target, array $base, array $parties, ?Closure $merge, ?array $line, array &$ids): void
    {
        if (!$target->exists) {
            LetterWrite::begin($target, $user);
        }
        LetterWrite::inherit($target, $source);

        $recipient = (array) ($source->spec['recipient'] ?? []);
        if ($line !== null) {
            $recipient = [
                'organization' => $line['organization'],
                'person' => $line['person'] ?: ($recipient['person'] ?? ''),
                'title' => $recipient['title'] ?? '',
            ];
        }

        $subject = (string) $source->subject;
        $target->subject = LetterRules::plain($merge ? $merge($subject, false) : $subject, 200);
        $target->parties = $parties;
        $target->recipients = LetterRules::recipientIds($parties);
        $images = array_filter($base, fn (array $block) => ($block['type'] ?? null) === 'image') !== [];
        $target->spec = [
            'blocks' => $images ? [LetterForm::BLANK_BLOCK] : $this->blocks($base, $target, $merge),
            'attachments' => $target->spec['attachments'] ?? [],
            'recipient' => $recipient,
        ];
        LetterFlow::writeSteps($target);
        $target->save();
        $ids[] = (int) $target->id;

        if ($images) {
            $target->spec = [...$target->spec, 'blocks' => $this->blocks($base, $target, $merge)];
        }
        LetterWrite::commit($target, $user, '', false);
    }

    private function base(Correspondence $source): array
    {
        $disk = Storage::disk('local');
        $base = [];
        foreach ($source->spec['blocks'] ?? [] as $block) {
            if (!is_array($block)) {
                continue;
            }
            $block = array_intersect_key($block, array_flip(Correspondence::BLOCK_KEYS));
            if (($block['type'] ?? null) === 'image') {
                $path = $block['path'] ?? null;
                if (!is_string($path) || !LetterRules::ownsPath($path, (int) $source->id) || !$disk->exists($path)) {
                    continue;
                }
            }
            $base[] = $block;
        }

        return $base;
    }

    private function blocks(array $base, Correspondence $target, ?Closure $merge): array
    {
        $blocks = [];
        foreach ($base as $block) {
            if (($block['type'] ?? null) === 'image') {
                $dest = LetterRules::directory((int) $target->id) . '/' . basename($block['path']);
                Storage::disk('local')->copy($block['path'], $dest);
                $caption = $block['caption'] ?? null;
                $blocks[] = [...$block, 'path' => $dest, 'caption' => $merge !== null && is_string($caption) ? $merge($caption, false) : $caption];

                continue;
            }
            if ($merge !== null) {
                foreach (['heading', 'text', 'html'] as $key) {
                    if (is_string($block[$key] ?? null)) {
                        $block[$key] = $merge($block[$key], $key === 'html');
                    }
                }
            }
            $blocks[] = $block;
        }

        return $blocks === [] ? [LetterForm::BLANK_BLOCK] : $blocks;
    }

    private function mergeFields(string $text, array $line, string $date, bool $html): string
    {
        $values = array_combine(LetterForm::MERGE_TOKENS, [
            $line['person'] !== '' ? $line['person'] : $line['organization'],
            $line['organization'],
            $line['address'],
            $date,
        ]);

        return strtr($text, $html ? array_map(e(...), $values) : $values);
    }

    private function parseLines(array $lines): array
    {
        $parsed = [];
        foreach ($lines as $line) {
            if (!is_string($line)) {
                continue;
            }
            $parts = array_map('trim', explode('|', LetterRules::plain($line, 700), 3));
            $org = LetterRules::plain($parts[0], 200);
            if ($org === '') {
                continue;
            }
            $parsed[] = [
                'organization' => $org,
                'person' => LetterRules::plain($parts[1] ?? '', 200),
                'address' => LetterRules::plain($parts[2] ?? '', 300),
            ];
        }

        return $parsed;
    }

    private function scoped(User $user, array $meta, string $scope): array
    {
        in_array($scope, LetterForm::SCOPES, true) || LetterRules::invalid('templateScope');

        if ($scope === 'department') {
            $department = $user->profile?->department_id;
            filled($department) || LetterRules::invalid('templateScope');
            $meta['department'] = $department;
        } else {
            unset($meta['department']);
        }
        $meta['template_scope'] = $scope;

        return $meta;
    }

    private function freshDraft(User $user, ?int $intoId): Correspondence
    {
        if ($intoId === null) {
            return new Correspondence();
        }

        $draft = Correspondence::query()->whereKey($intoId)->lockForUpdate()->first();
        if (!$draft || !self::isBlank($draft, $user)) {
            LetterRules::deny();
        }

        return $draft;
    }

    private function guardDirection(Correspondence $source, User $user): void
    {
        if ($source->direction === Correspondence::DIRECTION_IN) {
            CorrespondenceAccessPolicy::isRegistry($user) || LetterRules::deny();
        }
    }

    private function discard(array $ids): void
    {
        foreach ($ids as $id) {
            Storage::disk('local')->deleteDirectory(LetterRules::directory($id));
        }
    }
}
