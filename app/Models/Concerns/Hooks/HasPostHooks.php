<?php

namespace App\Models\Concerns\Hooks;

trait HasPostHooks
{
    protected static function bootHasPostHooks(): void
    {
        static::deleting(self::deleteImageOnDeleting(...));
    }

    private static function deleteImageOnDeleting(self $post)
    {
        return static::deleteStoredFiles($post->image);
    }
}
