<?php

namespace App\Models\Concerns\Hooks;

use App\Jobs\SendChatPushJob;
use App\Services\Messaging\PollDigestService;
use Illuminate\Support\Facades\DB;

trait HasMessageHooks
{
    protected static function bootHasMessageHooks(): void
    {
        static::forceDeleted(self::deleteAttachmentsOnForceDeleted(...));
        static::created(fn(self $message) => self::bumpDmThreadDigestAfterCommit($message, false));
        static::created(fn(self $message) => SendChatPushJob::fire('contact', (int) $message->recipient_id, (int) $message->sender_id));
        static::updated(self::bumpDmThreadDigestAfterCommit(...));
        static::deleted(self::bumpDmThreadDigestAfterCommit(...));
    }

    private static function deleteAttachmentsOnForceDeleted(self $message)
    {
        return static::deleteStoredFiles($message->attachments);
    }

    private static function bumpDmThreadDigestAfterCommit(self $message, bool $pairVersion = true)
    {
        return DB::afterCommit(function () use ($message, $pairVersion) {
            PollDigestService::bump("dm:sv:{$message->sender_id}");
            PollDigestService::bump("dm:sv:{$message->recipient_id}");

            if ($pairVersion) {
                PollDigestService::bump(self::pairVersionKey($message->sender_id, $message->recipient_id));
            }
        });
    }
}
