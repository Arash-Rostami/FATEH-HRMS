<?php

namespace App\Models\Concerns\Hooks;

trait HasReplyHooks
{
    protected static function bootHasReplyHooks(): void
    {
        static::deleting(self::deleteFilesOnDeleting(...));
    }

    private static function deleteFilesOnDeleting(self $reply)
    {
        return static::deleteStoredFiles($reply->files);
    }
}
