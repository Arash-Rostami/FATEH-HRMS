<?php

namespace App\Models\Concerns\Hooks;

trait HasLinkHooks
{
    protected static function bootHasLinkHooks(): void
    {
        static::saving(self::normalizeExtraOnSaving(...));
        static::deleting(self::deleteImageAndIconOnDeleting(...));
    }

    private static function normalizeExtraOnSaving(self $link)
    {
        $link->extra = $link->normalizedExtra();
    }

    private static function deleteImageAndIconOnDeleting(self $link)
    {
        static::deleteStoredFiles($link->image);
        static::deleteStoredFiles($link->icon);
    }
}
