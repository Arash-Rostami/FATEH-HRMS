<?php

namespace App\Models\Concerns\Hooks;

trait HasPhotoHooks
{
    protected static function bootHasPhotoHooks(): void
    {
        static::deleting(self::deletePathFilesOnDeleting(...));
    }

    private static function deletePathFilesOnDeleting(self $photo)
    {
        return static::deleteStoredFiles($photo->path);
    }
}
