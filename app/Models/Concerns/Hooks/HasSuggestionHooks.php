<?php

namespace App\Models\Concerns\Hooks;

trait HasSuggestionHooks
{
    protected static function bootHasSuggestionHooks(): void
    {
        static::deleting(self::deleteAttachmentOnDeleting(...));
    }

    private static function deleteAttachmentOnDeleting(self $suggestion)
    {
        return static::deleteStoredFiles($suggestion->attachment);
    }
}
