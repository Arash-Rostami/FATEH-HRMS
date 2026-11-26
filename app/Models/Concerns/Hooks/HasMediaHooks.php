<?php

namespace App\Models\Concerns\Hooks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

trait HasMediaHooks
{
    private const FILE_FIELDS = ['cover_image', 'file_path', 'transcript_path', 'chapters_path'];

    protected static function bootHasMediaHooks(): void
    {
        static::saving(self::guardExpiryBeforeDateOnSaving(...));
        static::saving(self::guardStructureOnSaving(...));
        static::saving(self::probeDurationOnSaving(...));
        static::saving(self::normalizeDurationOnSaving(...));
        static::updated(self::cleanReplacedFilesOnUpdated(...));
        static::deleting(self::deleteEpisodesOnDeleting(...));
        static::deleted(self::deleteFilesOnDeleted(...));
    }

    public static function writeSystemDurations(array $secondsById): void
    {
        $clean = [];

        foreach ($secondsById as $id => $seconds) {
            $id = (int) $id;
            $seconds = (int) $seconds;

            if ($id > 0 && $seconds >= 1) {
                $clean[$id] = $seconds;
            }
        }

        if ($clean === []) {
            return;
        }

        $case = 'CASE `id`';

        foreach ($clean as $id => $seconds) {
            $case .= ' WHEN '.$id.' THEN '.$seconds;
        }

        DB::table((new static())->getTable())
            ->whereIn('id', array_keys($clean))
            ->where(fn ($inner) => $inner->whereNull('duration_seconds')->orWhere('duration_seconds', '<', 1))
            ->update(['duration_seconds' => DB::raw($case.' ELSE `duration_seconds` END')]);
    }

    private static function probeDurationOnSaving(self $media): void
    {
        if ($media->duration_seconds !== null || ! is_string($media->file_path) || $media->file_path === '' || ! $media->isDirty('file_path')) {
            return;
        }

        try {
            $media->duration_seconds = static::probeDuration(Storage::disk('public')->path($media->file_path));
        } catch (Throwable) {
            $media->duration_seconds = null;
        }
    }

    private static function normalizeDurationOnSaving(self $media): void
    {
        if (($media->duration_seconds ?? 1) < 1) {
            $media->duration_seconds = null;
        }
    }

    private static function guardStructureOnSaving(self $media): void
    {
        $isSeries = $media->kind === self::KIND_SERIES;

        if ($isSeries && $media->isDirty('kind') && $media->exists && $media->getOriginal('kind') !== self::KIND_SERIES && filled($media->getOriginal('file_path'))) {
            throw ValidationException::withMessages(['data.kind' => 'ابتدا رسانه را غیرفعال کنید، فایل را بردارید و تغییرات را ذخیره کنید. سپس نوع رسانه را به «سری» تغییر دهید.']);
        }

        if ($isSeries) {
            $stray = array_values(array_filter(array_map(
                fn (string $field) => $field !== 'cover_image' && $media->isDirty($field) && is_string($media->{$field}) ? $media->{$field} : null,
                self::FILE_FIELDS
            )));

            $media->file_path = $media->duration_seconds = $media->transcript_path = $media->chapters_path = null;

            if ($stray !== []) {
                DB::afterCommit(fn () => static::deleteStoredFiles($stray));
            }
        }

        if (!$isSeries && ($media->isDirty('active') || !$media->exists) && ($media->active ?? !$media->exists) && blank($media->file_path)) {
            throw ValidationException::withMessages(['data.active' => __('resources/media/strings.validation.activate_needs_file')]);
        }

        if (!$media->isDirty(['parent_id', 'kind'])) {
            return;
        }

        if ($media->parent_id !== null) {
            $parent = $isSeries ? null : static::query()->whereKey($media->parent_id)->where('kind', self::KIND_SERIES)->whereNull('parent_id')->select('id')->withMax('episodes', 'position')->first();

            if ($parent === null) {
                throw ValidationException::withMessages(['data.parent_id' => 'برای افزودن جلسه، یک سری مستقل انتخاب کنید. سری نمی‌تواند زیرمجموعهٔ سری دیگری باشد.']);
            }

            if ($media->isDirty('parent_id') && ($media->exists || (int) $media->position < 1)) {
                $media->position = (int) $parent->episodes_max_position + 1;
            }
        }

        if ($media->exists && !$isSeries && $media->getOriginal('kind') === self::KIND_SERIES && $media->episodes()->exists()) {
            throw ValidationException::withMessages(['data.kind' => 'برای تغییر نوع سری، ابتدا جلسه‌های آن را جابه‌جا یا حذف کنید.']);
        }
    }

    private static function guardExpiryBeforeDateOnSaving(self $media): void
    {
        if ($media->isDirty(['report_date', 'expires_at']) && $media->report_date && $media->expires_at && $media->expires_at->lt($media->report_date)) {
            throw ValidationException::withMessages(['data.expires_at' => __('resources/report/strings.validation.expiry_before_date')]);
        }
    }

    private static function cleanReplacedFilesOnUpdated(self $media): void
    {
        $replaced = [];

        foreach (self::FILE_FIELDS as $field) {
            $previous = $media->getOriginal($field);

            if ($media->wasChanged($field) && is_string($previous)) {
                $replaced[] = $previous;
            }
        }

        if ($replaced !== []) {
            DB::afterCommit(fn () => static::deleteStoredFiles($replaced));
        }
    }

    private static function deleteEpisodesOnDeleting(self $media): void
    {
        if ($media->kind !== self::KIND_SERIES) {
            return;
        }

        static::query()->where('parent_id', $media->id)->lazyById()->each(fn (self $episode) => $episode->delete());
    }

    private static function deleteFilesOnDeleted(self $media): void
    {
        $paths = array_values(array_filter(array_map(fn (string $field) => $media->{$field}, self::FILE_FIELDS), 'is_string'));

        if ($paths !== []) {
            DB::afterCommit(fn () => static::deleteStoredFiles($paths));
        }
    }
}
