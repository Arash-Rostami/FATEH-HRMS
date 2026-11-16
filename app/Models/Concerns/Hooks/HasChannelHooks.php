<?php

namespace App\Models\Concerns\Hooks;

use App\Enums\ChannelRole;
use App\Enums\ChannelType;
use App\Models\Lock;
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
        static::forceDeleted(self::deleteOwnerKeyOnForceDeleted(...));
        static::updated(self::attachNewOwnerOnUpdated(...));
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

    private static function attachNewOwnerOnUpdated(self $channel)
    {
        if (!$channel->wasChanged('owner_id') || !$channel->owner_id) {
            return;
        }

        if ($channel->memberUsers()->whereKey($channel->owner_id)->exists()) {
            DB::table('channel_members')->where('channel_id', $channel->id)->where('user_id', $channel->owner_id)->update(['role' => ChannelRole::Member->value]);
            PollDigestService::bump("ch:rv:{$channel->id}");
            return;
        }

        self::attachOwnerOnCreated($channel);
        PollDigestService::bump("ch:rv:{$channel->id}");
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

    private static function deleteOwnerKeyOnForceDeleted(self $channel)
    {
        Lock::query()->where('lockable_type', 'channel')->where('lockable_id', $channel->id)->get()->each->delete();
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
