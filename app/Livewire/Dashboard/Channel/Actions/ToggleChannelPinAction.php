<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Services\Messaging\PollDigestService;

class ToggleChannelPinAction
{
    public function execute(int $channelId, int $messageId, int $userId, bool $onlyUnpin = false): bool
    {
        abort_unless(auth()->user()?->isActive(), 403);

        $channel = Channel::withoutTrashed()
            ->whereKey($channelId)
            ->whereHas('memberUsers', fn($q) => $q->where('users.id', $userId))
            ->first();

        if (!$channel) {
            return false;
        }

        $unpin = $onlyUnpin || (int) $channel->pinned_message_id === $messageId;

        if (!$unpin && !ChannelMessage::withoutTrashed()->where('channel_id', $channelId)->whereKey($messageId)->exists()) {
            return false;
        }

        Channel::whereKey($channelId)
            ->when($onlyUnpin, fn($q) => $q->where('pinned_message_id', $messageId))
            ->update(['pinned_message_id' => $unpin ? null : $messageId]);

        PollDigestService::bump("ch:rv:{$channelId}");

        return true;
    }
}
