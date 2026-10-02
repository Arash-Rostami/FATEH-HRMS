<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;

class DeleteChannelAction
{
    public function execute(int $channelId, int $userId): bool
    {
        $channel = Channel::withoutTrashed()
            ->where('owner_id', $userId)
            ->find($channelId);

        if (!$channel || $channel->memberUsers()->count() !== 1) {
            return false;
        }

        return (bool) $channel->delete();
    }
}
