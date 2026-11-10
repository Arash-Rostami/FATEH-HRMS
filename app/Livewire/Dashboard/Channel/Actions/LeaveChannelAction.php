<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Jobs\ReconcileEdgeJob;
use App\Jobs\ReconcileNudgeJob;
use App\Models\Channel;

class LeaveChannelAction
{
    public function execute(int $channelId, int $userId): bool
    {
        $channel = Channel::withoutTrashed()->find($channelId);

        if (!$channel || (int) $channel->owner_id === $userId) {
            return false;
        }

        $detached = $channel->memberUsers()->detach($userId);

        if ($detached) {
            dispatch(new ReconcileNudgeJob('channels-controller:nudge', Channel::class, $channelId))->afterCommit();
            dispatch(new ReconcileEdgeJob('channels-controller:edge', Channel::class, $channelId))->afterCommit();
        }

        return (bool) $detached;
    }
}