<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMember;

class LeaveChannelAction
{
    public function execute(int $channelId, int $userId): void
    {
        ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->delete();
    }
}