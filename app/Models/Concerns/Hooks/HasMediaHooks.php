<?php

namespace App\Models\Concerns\Hooks;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait HasMediaHooks
{
    private const FILE_FIELDS = ['cover_image', 'file_path', 'transcript_path', 'chapters_path'];

    protected static function bootHasMediaHooks(): void
    {
        static::saving(self::guardExpiryBeforeDateOnSaving(...));
        static::saving(self::guardStructureOnSaving(...));
        static::saving(self::normalizeDurationOnSaving(...));
        static::saving(self::clearEpisodeSeenByOnSaving(...));
        static::updated(self::cleanReplacedFilesOnUpdated(...));
        static::deleting(self::deleteEpisodesOnDeleting(...));
        static::deleted(self::deleteFilesOnDeleted(...));
    }

    public static function writeSystemDurations(array $secondsById): void
    {
        self::writeCases('duration_seconds', $secondsById, 1, true);
    }

    public static function writePositions(array $positions): void
    {
        self::writeCases('position', $positions, 0, false);
    }

    private static function writeCases(string $column, array $pairs, int $min, bool $onlyNull): void
    {
        $clean = [];

        foreach ($pairs as $id => $value) {
            $id = (int) $id;
            $value = (int) $value;

            if ($id > 0 && $value >= $min) {
                $clean[$id] = $value;
            }
        }

        if ($clean === []) {
            return;
        }

        $case = 'CASE `id`';

        foreach ($clean as $id => $value) {
            $case .= ' WHEN '.$id.' THEN '.$value;
        }

        $query = DB::table((new static())->getTable())->whereIn('id', array_keys($clean));

        if ($onlyNull) {
            $query->where(fn ($inner) => $inner->whereNull($column)->orWhere($column, '<', 1));
        }

        $query->update([$column => DB::raw($case.' ELSE `'.$column.'` END')]);
    }

    private static function clearEpisodeSeenByOnSaving(self $media): void
    {
        if ($media->parent_id) {
            $media->seen_by = null;
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
        if (!$media->isDirty(['parent_id', 'kind'])) {
            return;
        }

        $isSeries = $media->kind === self::KIND_SERIES;

        if ($media->parent_id !== null) {
            $validParent = !$isSeries
                && static::query()->whereKey($media->parent_id)->where('kind', self::KIND_SERIES)->whereNull('parent_id')->exists();

            if (!$validParent) {
                throw ValidationException::withMessages(['data.parent_id' => 'والد باید یک سری مستقل باشد و یک سری نمی‌تواند والد داشته باشد.']);
            }
        }

        if ($media->exists && !$isSeries && $media->getOriginal('kind') === self::KIND_SERIES && $media->episodes()->exists()) {
            throw ValidationException::withMessages(['data.kind' => 'سری دارای قسمت را نمی‌توان به نوع دیگر تغییر داد.']);
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
