<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Enums\ChannelRole;
use App\Jobs\ReconcileEdgeJob;
use App\Jobs\ReconcileNudgeJob;
use App\Models\Channel;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use App\Support\ChannelAccessPolicy;
use Illuminate\Support\Facades\DB;

class SyncChannelMembersAction
{
    public function execute(int $channelId, int $actorId, array $recipientIds, array $silence = [], array $restore = []): array
    {
        $channel = Channel::withoutTrashed()->find($channelId);
        $actor = User::find($actorId);

        if (! $channel || ! ChannelAccessPolicy::canManageMembers($channel, $actor)) {
            return ['added' => 0, 'removed' => 0, 'silenced' => 0];
        }

        $protected = $this->protectedIds($channel, $actor, $actorId);

        $result = DB::transaction(function () use ($channel, $protected, $recipientIds, $silence, $restore): array {
            $recipients = $this->normalizeRecipients($recipientIds, $protected);
            $current = $channel->memberUsers()->pluck('users.id')->all();

            $toAdd = array_values(array_diff($recipients, $current));
            $toRemove = array_values(array_diff($current, $recipients, $protected));

            return [
                'added' => $toAdd ? (int) $this->insertMembers($channel, $toAdd) : 0,
                'removed' => $toRemove ? (int) $channel->memberUsers()->detach($toRemove) : 0,
                'silenced' => $channel->isProjectLinked()
                    ? 0
                    : $this->switchRole($channel, $silence, $protected, ChannelRole::Member, ChannelRole::Viewer)
                        + $this->switchRole($channel, $restore, $protected, ChannelRole::Viewer, ChannelRole::Member),
            ];
        });

        if ($result['added'] || $result['removed']) {
            dispatch(new ReconcileNudgeJob('channels-controller:nudge', Channel::class, $channelId))->afterCommit();
            dispatch(new ReconcileEdgeJob('channels-controller:edge', Channel::class, $channelId))->afterCommit();
        }

        if ($result['silenced']) {
            PollDigestService::bump("ch:rv:{$channelId}");
        }

        return $result;
    }

    private function switchRole(Channel $channel, array $ids, array $protected, ChannelRole $from, ChannelRole $to): int
    {
        $ids = array_values(array_diff(array_map('intval', $ids), $protected));

        return $ids
            ? DB::table('channel_members')->where('channel_id', $channel->id)->whereIn('user_id', $ids)->where('role', $from->value)->update(['role' => $to->value])
            : 0;
    }

    private function protectedIds(Channel $channel, ?User $actor, int $actorId): array
    {
        return collect([$channel->owner_id, $actorId])
            ->when(
                ! ChannelAccessPolicy::isOwner($channel, $actor),
                fn ($ids) => $ids->merge(
                    DB::table('channel_members')
                        ->where('channel_id', $channel->id)
                        ->where('role', ChannelRole::Admin->value)
                        ->pluck('user_id')
                )
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeRecipients(array $recipientIds, array $protected): array
    {
        $ids = collect($recipientIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->diff($protected)
            ->unique()
            ->values()
            ->all();

        return $ids
            ? User::query()->visibleOnBoard()->whereKey($ids)->pluck('id')->all()
            : [];
    }

    private function insertMembers(Channel $channel, array $userIds): int
    {
        $now = now();

        return $channel->memberUsers()->newPivotStatement()->insertOrIgnore(
            array_map(fn (int $userId) => [
                'channel_id' => $channel->id,
                'user_id' => $userId,
                'joined_at' => $now,
                'entered_at' => null,
                'last_read_message_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $userIds)
        );
    }
}