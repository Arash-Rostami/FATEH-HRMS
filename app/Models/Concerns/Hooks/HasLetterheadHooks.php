<?php

namespace App\Models\Concerns\Hooks;

use App\Models\Letterhead;
use App\Services\Correspondence\CorrespondenceFonts;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait HasLetterheadHooks
{
    protected static function bootHasLetterheadHooks(): void
    {
        static::saving(self::validateLetterheadColumnsOnSaving(...));
        static::saving(self::rejectBadParentOnSaving(...));
        static::saving(self::rejectBadBorrowOnSaving(...));
        static::saving(self::clampLetterheadTextOnSaving(...));
        static::deleting(self::rejectUsedLetterheadOnDeleting(...));
        static::updated(self::deleteReplacedImagesOnUpdated(...));
        static::deleted(self::deleteImagesOnDeleted(...));
    }

    private static function invalidValue(string $column): ValidationException
    {
        return ValidationException::withMessages([$column => __('resources/correspondence/strings.errors.invalid_value')]);
    }

    private static function validateLetterheadColumnsOnSaving(Letterhead $letterhead): void
    {
        $changed = fn (string $column) => $letterhead->isDirty($column) || !$letterhead->exists;

        $allowed = [
            'kind' => [Letterhead::KIND_HEAD, Letterhead::KIND_REGISTRY],
            'size' => [Letterhead::SIZE_A4, Letterhead::SIZE_A5, Letterhead::SIZE_FIT],
            'lang' => (array) config('ai.correspondence.languages'),
            'sign_layout' => [1, 2, 3],
        ];

        foreach ($allowed as $column => $values) {
            if ($changed($column) && !in_array($letterhead->{$column}, $values, true)) {
                throw self::invalidValue($column);
            }
        }

        if ($changed('style') && is_array($letterhead->style)) {
            foreach (['font' => 'fa', 'latin_font' => 'latin'] as $key => $script) {
                $value = $letterhead->style[$key] ?? null;

                if ($value !== null && !(is_string($value) && in_array($value, CorrespondenceFonts::known($script), true))) {
                    throw self::invalidValue('style');
                }
            }
        }

        if ($changed('start_at') && (!is_int($letterhead->start_at) || $letterhead->start_at < 1)) {
            throw self::invalidValue('start_at');
        }

        if ($letterhead->exists && $letterhead->isDirty(['kind', 'active']) && self::isBorrowedFrom($letterhead)) {
            throw ValidationException::withMessages([$letterhead->isDirty('active') ? 'active' : 'kind' => __('resources/correspondence/strings.errors.letterhead_borrowed_change')]);
        }

        if (($changed('pattern') || $changed('kind') || $changed('parent_id')) && (!$letterhead->exists || array_key_exists('pattern', $letterhead->getAttributes()))) {
            $pattern = $letterhead->pattern;
            $rest = is_string($pattern) && mb_strlen($pattern) <= 120
                ? preg_replace('/\{(?:PREFIX|DEPT|TYPE|YYYY|YY|MM|SEQ(?::[1-9]\d?)?)\}/', '', $pattern)
                : null;

            if (filled($pattern) && ($rest === null || str_contains($rest, '{') || str_contains($rest, '}'))) {
                throw self::invalidValue('pattern');
            }

            if (blank($letterhead->parent_id) && !(is_string($pattern) && preg_match('/\{SEQ(?::[1-9]\d?)?\}/', $pattern))) {
                throw self::invalidValue('pattern');
            }
        }

        foreach (['header_image', 'footer_image', 'stamp_image'] as $column) {
            if (!$changed($column) || blank($letterhead->{$column})) {
                continue;
            }

            $path = $letterhead->{$column};

            if (!(is_string($path) && strlen($path) <= 255 && str_starts_with($path, 'letterheads/') && strlen($path) > strlen('letterheads/')
                && !str_ends_with($path, '/') && !preg_match('/[\x00-\x1F\x7F]/', $path) && self::isSafeStoredPath($path))) {
                throw self::invalidValue($column);
            }

            $taken = Letterhead::query()
                ->where(fn ($query) => $query->where('header_image', $path)->orWhere('footer_image', $path)->orWhere('stamp_image', $path))
                ->when($letterhead->exists, fn ($query) => $query->whereKeyNot($letterhead->id))
                ->exists();

            if ($taken) {
                throw self::invalidValue($column);
            }
        }
    }

    private static function rejectBadParentOnSaving(Letterhead $letterhead): void
    {
        if (array_key_exists('parent_id', $letterhead->getAttributes()) && blank($letterhead->parent_id)) {
            $letterhead->parent_id = null;
        }

        if (!$letterhead->isDirty(['parent_id', 'kind'])) {
            return;
        }

        if ($letterhead->exists && $letterhead->isDirty('kind')) {
            if ($letterhead->children()->where('kind', '!=', $letterhead->kind)->exists()) {
                throw ValidationException::withMessages(['kind' => __('resources/correspondence/strings.errors.letterhead_children_kind')]);
            }

            if ($letterhead->correspondences()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['kind' => __('resources/correspondence/strings.errors.letterhead_kind_in_use')]);
            }
        }

        if (blank($letterhead->parent_id)) {
            return;
        }

        if ($letterhead->exists && (int) $letterhead->parent_id === (int) $letterhead->id) {
            throw ValidationException::withMessages(['parent_id' => __('resources/correspondence/strings.errors.letterhead_self_parent')]);
        }

        $parentKey = $letterhead->parent_id;

        $parent = (is_int($parentKey) || is_string($parentKey)) && ctype_digit((string) $parentKey)
            ? Letterhead::whereKey((int) $parentKey)->first(['id', 'kind', 'parent_id'])
            : null;

        if ($parent === null) {
            throw ValidationException::withMessages(['parent_id' => __('resources/correspondence/strings.errors.letterhead_parent_missing')]);
        }

        if ($parent->parent_id !== null) {
            throw ValidationException::withMessages(['parent_id' => __('resources/correspondence/strings.errors.letterhead_parent_depth')]);
        }

        if ($parent->kind !== $letterhead->kind) {
            throw ValidationException::withMessages(['parent_id' => __('resources/correspondence/strings.errors.letterhead_parent_kind')]);
        }

        if ($letterhead->exists && $letterhead->isDirty('parent_id') && $letterhead->children()->exists()) {
            throw ValidationException::withMessages(['parent_id' => __('resources/correspondence/strings.errors.letterhead_has_children')]);
        }
    }

    private static function rejectBadBorrowOnSaving(Letterhead $letterhead): void
    {
        if (!$letterhead->isDirty(['config', 'kind']) || !is_array($letterhead->config)) {
            return;
        }

        $config = $letterhead->config;

        if ($letterhead->kind === Letterhead::KIND_REGISTRY) {
            $letterhead->config = array_merge($config, array_intersect_key(['header_from' => null, 'footer_from' => null], $config));

            return;
        }

        $borrows = false;
        $invalid = fn () => ValidationException::withMessages(['config' => __('resources/correspondence/strings.errors.letterhead_borrow_invalid')]);

        foreach (['header_from', 'footer_from'] as $key) {
            $value = $config[$key] ?? null;

            if (blank($value)) {
                if (array_key_exists($key, $config)) {
                    $config[$key] = null;
                }

                continue;
            }

            if (!(is_int($value) || is_string($value)) || !ctype_digit((string) $value)) {
                throw $invalid();
            }

            $row = Letterhead::whereKey((int) $value)->first(['id', 'kind', 'active', 'config']);

            if ($row === null || ($letterhead->exists && $row->id === $letterhead->id)
                || $row->kind !== Letterhead::KIND_HEAD || $letterhead->kind !== Letterhead::KIND_HEAD || !$row->active) {
                throw $invalid();
            }

            if (filled(data_get($row->config, 'header_from')) || filled(data_get($row->config, 'footer_from'))) {
                throw ValidationException::withMessages(['config' => __('resources/correspondence/strings.errors.letterhead_borrow_chain')]);
            }

            $config[$key] = (int) $value;
            $borrows = true;
        }

        if ($borrows && $letterhead->exists && self::isBorrowedFrom($letterhead)) {
            throw ValidationException::withMessages(['config' => __('resources/correspondence/strings.errors.letterhead_borrow_chain')]);
        }

        $letterhead->config = $config;
    }

    private static function isBorrowedFrom(Letterhead $letterhead): bool
    {
        return Letterhead::where('config->header_from', $letterhead->id)->orWhere('config->footer_from', $letterhead->id)->exists();
    }

    private static function clampLetterheadTextOnSaving(Letterhead $letterhead): void
    {
        foreach (['name', 'watermark'] as $column) {
            if (is_string($letterhead->{$column}) && mb_strlen($letterhead->{$column}) > 120) {
                $letterhead->{$column} = mb_substr($letterhead->{$column}, 0, 120);
            }
        }
    }

    private static function deleteReplacedImagesOnUpdated(Letterhead $letterhead): void
    {
        $old = [];

        foreach (['header_image', 'footer_image', 'stamp_image'] as $column) {
            if ($letterhead->wasChanged($column)) {
                $old[] = $letterhead->getOriginal($column);
            }
        }

        self::deleteLetterheadImagesAfterCommit(array_diff(
            $old,
            [$letterhead->header_image, $letterhead->footer_image, $letterhead->stamp_image],
        ));
    }

    private static function deleteImagesOnDeleted(Letterhead $letterhead): void
    {
        self::deleteLetterheadImagesAfterCommit([$letterhead->header_image, $letterhead->footer_image, $letterhead->stamp_image]);
    }

    private static function deleteLetterheadImagesAfterCommit(array $candidates): void
    {
        $paths = array_values(array_filter(
            $candidates,
            fn ($path) => is_string($path) && str_starts_with($path, 'letterheads/'),
        ));

        if ($paths) {
            DB::afterCommit(fn () => static::deleteStoredFiles($paths));
        }
    }

    private static function rejectUsedLetterheadOnDeleting(Letterhead $letterhead): void
    {
        if ($letterhead->correspondences()->withTrashed()->exists() || $letterhead->children()->exists()) {
            throw ValidationException::withMessages(['id' => __('resources/correspondence/strings.errors.letterhead_in_use')]);
        }

        if (self::isBorrowedFrom($letterhead)) {
            throw ValidationException::withMessages(['id' => __('resources/correspondence/strings.errors.letterhead_borrowed')]);
        }
    }
}
