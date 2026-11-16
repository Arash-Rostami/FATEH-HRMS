<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Jobs\ReconcileEdgeJob;
use App\Jobs\ReconcileNudgeJob;
use App\Models\Channel;
use App\Support\ChannelAccessPolicy;

class LeaveChannelAction
{
    public function execute(int $channelId, int $userId): bool
    {
        $channel = Channel::withoutTrashed()->find($channelId);

        if (! $channel || ! ChannelAccessPolicy::canLeave($channel, $userId)) {
            return false;
        }

        if (! $channel->memberUsers()->detach($userId)) {
            return false;
        }

        dispatch(new ReconcileNudgeJob('channels-controller:nudge', Channel::class, $channelId))->afterCommit();
        dispatch(new ReconcileEdgeJob('channels-controller:edge', Channel::class, $channelId))->afterCommit();

        return true;
    }
}