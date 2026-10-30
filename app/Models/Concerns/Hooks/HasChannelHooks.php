<?php

namespace App\Models\Concerns\Hooks;

use App\Enums\ChannelType;
use App\Services\Messaging\PollDigestService;
use Illuminate\Support\Facades\DB;

trait HasChannelHooks
{
    protected static function bootHasChannelHooks(): void
    {
        static::created(self::attachOwnerOnCreated(...));
        static::saving(self::privatizeProjectLinkedOnSaving(...));
        static::deleting(self::blockProjectLinkedDeleteOnDeleting(...));
        static::forceDeleted(self::deleteMessageDirectoryOnForceDeleted(...));
        static::updated(self::bumpThreadDigestOnUpdated(...));
        static::deleted(self::bumpThreadDigestOnDeleted(...));
    }

    private static function attachOwnerOnCreated(self $channel)
    {
        if ($channel->owner_id) {
            $channel->memberUsers()->attach($channel->owner_id, [
                'joined_at' => now(),
                'entered_at' => now(),
                'last_read_message_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private static function privatizeProjectLinkedOnSaving(self $channel)
    {
        if ($channel->exists && $channel->isDirty('type') && $channel->type === ChannelType::Open && $channel->isProjectLinked()) {
            $channel->type = ChannelType::Private;
        }
    }

    private static function blockProjectLinkedDeleteOnDeleting(self $channel)
    {
        return !($channel->isProjectLinked() && !$channel->isForceDeleting());
    }

    private static function deleteMessageDirectoryOnForceDeleted(self $channel)
    {
        return static::deleteStoredDirectory("channel_messages/{$channel->id}");
    }

    private static function bumpThreadDigestOnUpdated(self $channel)
    {
        if ($channel->wasChanged(['name', 'type', 'deleted_at'])) {
            DB::afterCommit(fn() => PollDigestService::bump("ch:tv:{$channel->id}"));
        }
    }

    private static function bumpThreadDigestOnDeleted(self $channel)
    {
        return DB::afterCommit(fn() => PollDigestService::bump("ch:tv:{$channel->id}"));
    }
}
