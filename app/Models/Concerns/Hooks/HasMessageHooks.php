<?php

namespace App\Models\Concerns\Hooks;

use App\Services\Messaging\PollDigestService;
use Illuminate\Support\Facades\DB;

trait HasMessageHooks
{
    protected static function bootHasMessageHooks(): void
    {
        static::forceDeleted(self::deleteAttachmentsOnForceDeleted(...));
        static::created(self::bumpDmThreadDigestAfterCommit(...));
        static::updated(self::bumpDmThreadDigestAfterCommit(...));
        static::deleted(self::bumpDmThreadDigestAfterCommit(...));
    }

    private static function deleteAttachmentsOnForceDeleted(self $message)
    {
        return static::deleteStoredFiles($message->attachments);
    }

    private static function bumpDmThreadDigestAfterCommit(self $message)
    {
        return DB::afterCommit(function () use ($message) {
            PollDigestService::bump("dm:sv:{$message->sender_id}");
            PollDigestService::bump("dm:sv:{$message->recipient_id}");
        });
    }
}
