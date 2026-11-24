<?php

namespace App\Models\Concerns\Hooks;

use App\Jobs\SendChatPushJob;
use App\Models\Project;
use App\Services\Messaging\PollDigestService;
use App\Services\ProjectTask\ProjectHeartbeat;
use Illuminate\Support\Facades\DB;

trait HasChannelMessageHooks
{
    protected static function bootHasChannelMessageHooks(): void
    {
        static::forceDeleted(self::deleteMessageDirectoryOnForceDeleted(...));
        static::created(self::bumpChannelThreadDigestAfterCommit(...));
        static::created(fn(self $message) => SendChatPushJob::fire('channel', (int) $message->channel_id, (int) $message->sender_id));
        static::updated(self::bumpChannelThreadDigestAfterCommit(...));
        static::deleted(self::bumpChannelThreadDigestAfterCommit(...));
    }

    private static function deleteMessageDirectoryOnForceDeleted(self $message)
    {
        return static::deleteStoredDirectory("channel_messages/{$message->channel_id}/{$message->id}");
    }

    private static function bumpChannelThreadDigestAfterCommit(self $message)
    {
        return DB::afterCommit(function () use ($message) {
            PollDigestService::bump("ch:tv:{$message->channel_id}");

            if ($projectId = Project::idForChannel($message->channel_id)) {
                ProjectHeartbeat::bump($projectId, 'chat');
            }
        });
    }
}
