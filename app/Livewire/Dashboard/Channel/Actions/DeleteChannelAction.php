<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;
use App\Models\User;
use App\Support\ChannelAccessPolicy;

class DeleteChannelAction
{
    public function execute(int $channelId, int $userId): bool
    {
        $channel = Channel::withoutTrashed()->find($channelId);

        if (!$channel || !ChannelAccessPolicy::canDelete($channel, User::find($userId)) || $channel->memberUsers()->count() !== 1 || $channel->isProjectLinked()) {
            return false;
        }

        return (bool) $channel->delete();
    }
}
