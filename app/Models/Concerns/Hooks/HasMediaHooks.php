<?php

namespace App\Models\Concerns\Hooks;

use App\Services\ContentSanitizerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait HasMediaHooks
{
    private const FILE_FIELDS = ['cover_image', 'file_path', 'transcript_path', 'chapters_path'];

    protected static function bootHasMediaHooks(): void
    {
        static::saving(self::sanitizeDescriptionOnSaving(...));
        static::saving(self::guardExpiryBeforeDateOnSaving(...));
        static::updating(self::cleanReplacedFilesOnUpdating(...));
        static::deleting(self::deleteEpisodesOnDeleting(...));
        static::deleting(self::deleteFilesOnDeleting(...));
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
            $query->whereNull($column);
        }

        $query->update([$column => DB::raw($case.' ELSE `'.$column.'` END')]);
    }

    private static function sanitizeDescriptionOnSaving(self $media): void
    {
        if (!$media->isDirty('description')) {
            return;
        }

        $media->attributes['description'] = ContentSanitizerService::clean($media->attributes['description'] ?? null);
    }

    private static function guardExpiryBeforeDateOnSaving(self $media): void
    {
        if ($media->isDirty(['report_date', 'expires_at']) && $media->report_date && $media->expires_at && $media->expires_at->lt($media->report_date)) {
            throw ValidationException::withMessages(['data.expires_at' => __('resources/report/strings.validation.expiry_before_date')]);
        }
    }

    private static function cleanReplacedFilesOnUpdating(self $media): void
    {
        foreach (self::FILE_FIELDS as $field) {
            if (!$media->isDirty($field)) {
                continue;
            }

            $previous = $media->getOriginal($field);

            if (is_string($previous) && $previous !== $media->{$field}) {
                static::deleteStoredFiles($previous);
            }
        }
    }

    private static function deleteEpisodesOnDeleting(self $media): void
    {
        $media->episodes()->get()->each(fn (self $episode) => $episode->delete());
    }

    private static function deleteFilesOnDeleting(self $media): void
    {
        foreach (self::FILE_FIELDS as $field) {
            static::deleteStoredFiles($media->{$field});
        }
    }
}
