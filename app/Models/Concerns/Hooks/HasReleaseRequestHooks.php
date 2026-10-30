<?php

namespace App\Models\Concerns\Hooks;

trait HasReleaseRequestHooks
{
    protected static function bootHasReleaseRequestHooks(): void
    {
        static::deleting(self::deleteAttachmentsOnDeleting(...));
    }

    private static function deleteAttachmentsOnDeleting(self $request)
    {
        return static::deleteStoredFiles($request->attachments);
    }
}
