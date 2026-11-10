<?php

namespace App\Models\Concerns\Hooks;

use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Services\ContentSanitizerService;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait HasCorrespondenceHooks
{
    protected static function bootHasCorrespondenceHooks(): void
    {
        static::updating(self::rejectFrozenEditsOnUpdating(...));
        static::saving(self::validateLinkedRecordsOnSaving(...));
        static::saving(self::validateAndScrubColumnsOnSaving(...));
        static::saving(self::cleanBodyAndSearchTextOnSaving(...));
        static::saving(self::cleanTagsOnSaving(...));
        static::saved(self::flushCorrespondenceMenuStateOnSaved(...));
        static::deleted(self::flushCorrespondenceMenuStateOnDeleted(...));
        static::restored(self::flushCorrespondenceMenuStateOnRestored(...));
        static::forceDeleted(self::deleteStoredFilesOnForceDeleted(...));
    }

    private static function rejectFrozenEditsOnUpdating(Correspondence $letter): void
    {
        $protected = ['spec', 'subject', 'number', 'number_seq', 'letter_date', 'letterhead_id', 'type', 'direction', 'sign_mode', 'content_hash', 'approved_at'];

        if (!$letter->isDirty([...$protected, 'status'])) {
            return;
        }

        $original = $letter->getRawOriginal('status')
            ?? Correspondence::withTrashed()->whereKey($letter->getKey())->toBase()->value('status');

        $exits = [
            Correspondence::STATUS_APPROVED => [Correspondence::STATUS_SUPERSEDED, Correspondence::STATUS_VOID],
            Correspondence::STATUS_SUPERSEDED => [Correspondence::STATUS_VOID],
            Correspondence::STATUS_VOID => [],
            Correspondence::STATUS_DONE => [],
        ];

        if (!array_key_exists($original, $exits)) {
            return;
        }

        $badExit = $letter->isDirty('status') && !in_array($letter->status, $exits[$original], true);

        if ($letter->isDirty($protected) || $badExit) {
            throw ValidationException::withMessages(['status' => __('resources/correspondence/strings.errors.frozen')]);
        }
    }

    private static function invalidValue(string $column): ValidationException
    {
        return ValidationException::withMessages([$column => __('resources/correspondence/strings.errors.invalid_value')]);
    }

    private static function tooLarge(): ValidationException
    {
        return ValidationException::withMessages(['spec' => __('resources/correspondence/strings.errors.body_too_large')]);
    }

    private static function validateLinkedRecordsOnSaving(Correspondence $letter): void
    {
        if (filled($letter->letterhead_id) && ($letter->isDirty(['letterhead_id', 'direction']) || !$letter->exists)) {
            $kind = $letter->direction === Correspondence::DIRECTION_IN ? Letterhead::KIND_REGISTRY : Letterhead::KIND_HEAD;

            if (!Letterhead::whereKey($letter->letterhead_id)->where('kind', $kind)->where('active', true)->exists()) {
                throw self::invalidValue('letterhead_id');
            }
        }

        foreach (['in_reply_to_id', 'supersedes_id'] as $link) {
            if (blank($letter->{$link}) || !($letter->isDirty($link) || !$letter->exists)) {
                continue;
            }

            $target = Correspondence::withTrashed()->whereKey($letter->{$link})->toBase()->first(['id', 'direction']);

            if ($target === null || ($link === 'supersedes_id' && $target->direction !== $letter->direction)) {
                throw self::invalidValue($link);
            }

            $current = (int) $letter->{$link};

            for ($depth = 0; $letter->exists && $current > 0 && $depth < 25; $depth++) {
                if ($current === (int) $letter->id) {
                    throw ValidationException::withMessages([$link => __('resources/correspondence/strings.errors.self_link')]);
                }

                $current = (int) Correspondence::withTrashed()->whereKey($current)->toBase()->value($link);
            }
        }
    }

    private static function plainLine(mixed $value, int $limit, string $column): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = (string) $value;
        $line = mb_check_encoding($value, 'UTF-8') ? preg_replace('/[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $value) : null;
        $line = $line === null ? null : self::stripTags($line);
        $line = $line === null ? null : preg_replace('/[\x00-\x1F\x7F\s]+/u', ' ', $line);

        if ($line === null) {
            throw self::invalidValue($column);
        }

        return mb_substr(trim($line), 0, $limit);
    }

    private static function stripTags(string $value): ?string
    {
        for ($pass = 0; $pass < 5; $pass++) {
            $stripped = preg_replace('/<\/?[a-z][^<>]*+>/i', '', $value);

            if ($stripped === null || $stripped === $value) {
                return $stripped;
            }

            $value = $stripped;
        }

        return preg_replace('/<(?=\/?[a-z])/i', '', $value);
    }

    private static function validateAndScrubColumnsOnSaving(Correspondence $letter): void
    {
        $allowed = [
            'status' => array_keys(Correspondence::STATUS_COLORS),
            'direction' => [Correspondence::DIRECTION_OUT, Correspondence::DIRECTION_IN],
            'mode' => [Correspondence::MODE_SIMPLE, Correspondence::MODE_SMART],
            'classification' => [Correspondence::CLASSIFICATION_NORMAL, Correspondence::CLASSIFICATION_CONFIDENTIAL, Correspondence::CLASSIFICATION_SECRET],
            'urgency' => [Correspondence::URGENCY_NORMAL, Correspondence::URGENCY_URGENT, Correspondence::URGENCY_IMMEDIATE],
            'sign_mode' => [Correspondence::SIGN_MODE_ELECTRONIC, Correspondence::SIGN_MODE_WET],
            'route_mode' => [Correspondence::ROUTE_SEQUENTIAL, Correspondence::ROUTE_PARALLEL],
            'sender_category' => [Correspondence::CATEGORY_NORMAL, Correspondence::CATEGORY_AUTHORITY],
            'type' => array_keys((array) config('ai.correspondence.types')),
            'related_type' => (array) config('ai.correspondence.related_types'),
        ];

        foreach ($allowed as $column => $values) {
            if (($letter->isDirty($column) || !$letter->exists) && $letter->{$column} !== null && !in_array($letter->{$column}, $values, true)) {
                throw self::invalidValue($column);
            }
        }

        if (($letter->isDirty(['type', 'direction']) || !$letter->exists) && filled($letter->type)) {
            $type = config("ai.correspondence.types.{$letter->type}");

            if (!($type['enabled'] ?? false) || ($letter->direction !== null && ($type['direction'] ?? null) !== $letter->direction)) {
                throw self::invalidValue('type');
            }
        }

        foreach (['subject' => 200, 'their_number' => 64] as $column => $limit) {
            if (is_string($letter->{$column})) {
                $letter->{$column} = self::plainLine($letter->{$column}, $limit, $column);
            }
        }

        if (filled($letter->their_date) && ($letter->isDirty('their_date') || !$letter->exists)) {
            if (!is_string($letter->their_date) || !preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', $letter->their_date, $parts)
                || $parts[2] < 1 || $parts[2] > 12 || $parts[3] < 1 || $parts[3] > ($parts[2] <= 6 ? 31 : 30)) {
                throw self::invalidValue('their_date');
            }

            $letter->their_date = sprintf('%04d/%02d/%02d', $parts[1], $parts[2], $parts[3]);
        } elseif (blank($letter->their_date) && $letter->their_date !== null) {
            $letter->their_date = null;
        }
    }

    private static function cleanBodyAndSearchTextOnSaving(Correspondence $letter): void
    {
        if ($letter->exists && !$letter->isDirty(['spec', 'classification'])) {
            return;
        }

        if ($letter->exists && !$letter->isDirty('spec')) {
            $stored = null;

            if ($letter->classification === Correspondence::CLASSIFICATION_NORMAL) {
                $stored = array_key_exists('spec', $letter->getAttributes())
                    ? $letter->spec
                    : json_decode((string) Correspondence::withTrashed()->whereKey($letter->getKey())->toBase()->value('spec'), true);
            }

            $letter->search_text = is_array($stored) ? self::correspondenceSearchText($stored) : null;

            return;
        }

        $spec = is_array($letter->spec) ? array_intersect_key($letter->spec, array_flip(Correspondence::SPEC_KEYS)) : [];
        $raw = is_array($spec['blocks'] ?? null) ? array_values($spec['blocks']) : [];

        if (count($raw) > (int) config('ai.correspondence.max_blocks', 60)) {
            throw self::tooLarge();
        }

        $storedSpec = $letter->exists ? json_decode((string) $letter->getRawOriginal('spec'), true) : null;
        $storedHtml = [];

        foreach (is_array($storedSpec['blocks'] ?? null) ? $storedSpec['blocks'] : [] as $storedBlock) {
            if (is_array($storedBlock) && is_string($storedBlock['html'] ?? null)) {
                $storedHtml[$storedBlock['html']] = true;
            }
        }

        $rawBytes = 0;

        foreach ($raw as $block) {
            $html = is_array($block) ? ($block['html'] ?? null) : null;
            $rawBytes += is_string($html) && !isset($storedHtml[$html]) ? strlen($html) : 0;
        }

        if ($rawBytes > (int) config('ai.correspondence.body_bytes', 200000)) {
            throw self::tooLarge();
        }

        $caps = ['text' => 20000, 'id' => 64];
        $blocks = [];

        foreach ($raw as $block) {
            if (!is_array($block)) {
                continue;
            }

            $block = array_intersect_key($block, array_flip(Correspondence::BLOCK_KEYS));
            $block['type'] = is_string($block['type'] ?? null) ? $block['type'] : 'html';

            if (!in_array($block['type'], Correspondence::STRUCTURAL_BLOCK_TYPES, true)) {
                continue;
            }

            if (array_key_exists('path', $block)) {
                $block['path'] = self::safeCorrespondencePath($letter, $block['path']);
            }

            if ($block['type'] === 'image' && ($block['path'] ?? null) === null) {
                continue;
            }

            if (is_string($block['text'] ?? null)) {
                $block['text'] = self::stripTags($block['text']) ?? '';
            }

            foreach (['heading', 'caption'] as $key) {
                if (array_key_exists($key, $block)) {
                    $block[$key] = is_string($block[$key]) ? self::plainLine($block[$key], 200, 'spec') : null;
                }
            }

            foreach ($caps as $key => $cap) {
                if (array_key_exists($key, $block)) {
                    $block[$key] = is_scalar($block[$key]) ? preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', mb_substr((string) $block[$key], 0, $cap)) : '';
                }
            }

            if (isset($block['size']) && !in_array($block['size'], Correspondence::BLOCK_SIZES, true)) {
                unset($block['size']);
            }

            if (isset($block['align']) && !in_array($block['align'], Correspondence::BLOCK_ALIGNS, true)) {
                unset($block['align']);
            }

            if (array_key_exists('html', $block)) {
                if (!is_string($block['html'])) {
                    $block['html'] = '';
                } elseif (!isset($storedHtml[$block['html']])) {
                    if (strlen($block['html']) > Correspondence::HTML_BLOCK_BYTES) {
                        throw self::tooLarge();
                    }

                    $block['html'] = Correspondence::sanitizeHtml(ContentSanitizerService::clean($block['html']));
                }
            }

            $blocks[] = $block;
        }

        $spec['blocks'] = $blocks;

        if (array_key_exists('attachments', $spec) && !is_array($spec['attachments'])) {
            unset($spec['attachments']);
        }

        if (is_array($spec['attachments'] ?? null)) {
            $kept = [];

            foreach ($spec['attachments'] as $attachment) {
                $path = is_array($attachment) ? self::safeCorrespondencePath($letter, $attachment['path'] ?? null) : null;

                if ($path !== null && !isset($kept[$path])) {
                    $kept[$path] = [
                        'path' => $path,
                        'name' => self::plainLine($attachment['name'] ?? '', 200, 'spec'),
                        'size' => max(0, (int) ($attachment['size'] ?? 0)),
                        'mime' => self::plainLine($attachment['mime'] ?? '', 100, 'spec'),
                    ];
                }
            }

            $spec['attachments'] = array_slice(array_values($kept), 0, (int) config('ai.correspondence.upload.max_files', 5));
        }

        foreach (['recipient' => ['organization', 'person', 'title'], 'sender' => ['name', 'organization', 'address']] as $key => $fields) {
            if (!array_key_exists($key, $spec)) {
                continue;
            }

            if (!is_array($spec[$key])) {
                unset($spec[$key]);

                continue;
            }

            $clean = [];

            foreach ($fields as $field) {
                $clean[$field] = self::plainLine($spec[$key][$field] ?? null, $field === 'address' ? 300 : 200, 'spec');
            }

            $spec[$key] = $clean;
        }

        if (array_key_exists('evidence_path', $spec) && self::safeCorrespondencePath($letter, $spec['evidence_path']) === null) {
            unset($spec['evidence_path']);
        }

        if (array_sum(array_map(fn (array $block) => strlen($block['html'] ?? ''), $blocks)) > (int) config('ai.correspondence.body_bytes', 200000)
            || strlen((string) json_encode($spec, JSON_UNESCAPED_UNICODE)) > (int) config('ai.correspondence.spec_bytes', 400000)) {
            throw self::tooLarge();
        }

        if ($letter->spec !== null || $blocks) {
            $letter->spec = $spec;
        }

        $letter->search_text = $letter->classification === Correspondence::CLASSIFICATION_NORMAL
            ? self::correspondenceSearchText($spec)
            : null;
    }

    private static function correspondenceSearchText(array $spec): ?string
    {
        $parts = array_map(
            fn ($block) => is_array($block)
                ? trim(($block['heading'] ?? '') . ' ' . ($block['caption'] ?? '') . ' ' . ($block['text'] ?? '') . ' ' . preg_replace('/<[^>]*>/', ' ', $block['html'] ?? ''))
                : '',
            is_array($spec['blocks'] ?? null) ? $spec['blocks'] : [],
        );

        foreach (['recipient', 'sender'] as $key) {
            $parts[] = is_array($spec[$key] ?? null) ? implode(' ', array_filter($spec[$key], 'is_string')) : '';
        }

        $text = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(implode(' ', array_filter($parts)))));

        return $text === '' ? null : mb_strcut($text, 0, (int) config('ai.correspondence.search_text_bytes', 16384));
    }

    private static function flushCorrespondenceMenuStateOnSaved(Correspondence $letter): void
    {
        $keys = $letter->wasRecentlyCreated ? array_keys($letter->getAttributes()) : array_keys($letter->getChanges());
        $relevant = array_values(array_intersect($keys, ['status', 'awaiting_user_id', 'recipients', 'parties', 'route_mode']));

        if (!$relevant) {
            return;
        }

        if ($letter->wasRecentlyCreated && blank($letter->recipients) && blank($letter->parties)) {
            return;
        }

        if ($relevant === ['status'] && in_array($letter->getOriginal('status'), Correspondence::DRAFT_LIKE, true) && in_array($letter->status, Correspondence::DRAFT_LIKE, true)) {
            return;
        }

        DB::afterCommit(fn () => StateService::flush());
    }

    private static function flushCorrespondenceMenuStateOnDeleted(Correspondence $letter): void
    {
        if (!in_array($letter->status, Correspondence::DRAFT_LIKE, true)) {
            DB::afterCommit(fn () => StateService::flush());
        }
    }

    private static function flushCorrespondenceMenuStateOnRestored(Correspondence $letter): void
    {
        DB::afterCommit(fn () => StateService::flush());
    }

    private static function deleteStoredFilesOnForceDeleted(Correspondence $letter): void
    {
        $id = (int) $letter->id;
        $temp = $letter->tempDirectory();

        DB::afterCommit(function () use ($id, $temp) {
            $disk = Storage::disk('local');

            if ($id > 0) {
                $disk->deleteDirectory("correspondence/{$id}");
            }

            $disk->deleteDirectory($temp);
        });
    }

    private static function cleanTagsOnSaving(Correspondence $letter): void
    {
        if ($letter->exists && !$letter->isDirty('tags')) {
            return;
        }

        if (!is_array($letter->tags)) {
            if ($letter->tags !== null) {
                $letter->tags = [];
            }

            return;
        }

        $letter->tags = collect($letter->tags)
            ->filter(fn ($tag) => is_string($tag))
            ->map(fn (string $tag) => self::plainLine($tag, 40, 'tags'))
            ->filter(fn ($tag) => $tag !== '')
            ->unique()
            ->take(20)
            ->values()
            ->all();
    }

    private static function safeCorrespondencePath(Correspondence $letter, mixed $path): ?string
    {
        return $letter->exists
            && is_string($path)
            && strlen($path) <= 255
            && str_starts_with($path, $prefix = "correspondence/{$letter->id}/")
            && strlen($path) > strlen($prefix)
            && !str_ends_with($path, '/')
            && !str_contains($path, '..')
            && !preg_match('/[\x00-\x1F\x7F]/', $path)
            ? $path
            : null;
    }
}
