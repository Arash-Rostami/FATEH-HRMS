<?php

namespace App\Models\Concerns\Hooks;

trait HasFeedHooks
{
    protected static function bootHasFeedHooks(): void
    {
        static::deleting(self::cleanRelationsAndFilesOnDeleting(...));
    }

    private static function cleanRelationsAndFilesOnDeleting(self $feed)
    {
        $feed->comments()->delete();
        $feed->reactions()->delete();
        $feed->polls()->delete();
        static::deleteStoredFiles($feed->media_paths);
    }
}
