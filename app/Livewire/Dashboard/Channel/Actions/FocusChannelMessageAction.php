<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMember;
use App\Models\ChannelMessage;

class FocusChannelMessageAction
{
    public function execute(int $channelId, int $messageId, int $userId, int $loadedLimit): ?bool
    {
        if ($channelId <= 0 || $messageId <= 0 || $userId <= 0) {
            return null;
        }

        $isMember = ChannelMember::query()
            ->where('user_id', $userId)
            ->where('channel_id', $channelId)
            ->exists();
        if (!$isMember) {
            return null;
        }

        $count = ChannelMessage::withoutTrashed()
            ->where('channel_id', $channelId)
            ->where('id', '>=', $messageId)
            ->orderBy('id')
            ->take($loadedLimit + 1)
            ->pluck('id')
            ->count();

        if ($count === 0) {
            return null;
        }

        return $count > $loadedLimit;
    }
}