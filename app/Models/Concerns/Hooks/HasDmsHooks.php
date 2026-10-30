<?php

namespace App\Models\Concerns\Hooks;

use App\Models\DMS;

trait HasDmsHooks
{
    protected static function bootHasDmsHooks(): void
    {
        static::saved(self::forgetListCachesOnSaved(...));
        static::updated(self::resetReadsOnUpdated(...));
        static::deleting(self::deleteFilesOnDeleting(...));
        static::deleted(self::deleteReadsOnDeleted(...));
    }

    private static function forgetListCachesOnSaved()
    {
        return static::forgetListCaches();
    }

    private static function resetReadsOnUpdated(DMS $document): void
    {
        if ($document->wasChanged('file') || $document->wasChanged('revision')) {
            $document->reads()->update(['read' => false, 'read_count' => 0]);
            $document->newQuery()->whereKey($document->getKey())->update(['combined_read_count' => 0]);
        }
    }

    private static function deleteFilesOnDeleting(DMS $document): void
    {
        static::deleteStoredFiles($document->file);
        static::deleteStoredFiles($document->extra_files);
    }

    private static function deleteReadsOnDeleted(DMS $document): void
    {
        $document->reads()->delete();
        static::forgetListCaches();
    }
}
