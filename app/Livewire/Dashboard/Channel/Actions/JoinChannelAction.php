<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\ChannelMessage;

class JoinChannelAction
{
    public function execute(int $channelId, int $userId): void
    {
        $channel = Channel::withoutTrashed()->find($channelId);

        abort_unless($channel && $channel->type === ChannelType::Open, 403, 'عضویت در این کانال ممکن نیست.');

        $channel->memberUsers()->newPivotStatement()->insertOrIgnore([
            'channel_id'           => $channelId,
            'user_id'              => $userId,
            'joined_at'            => now(),
            'entered_at'           => now(),
            'last_read_message_id' => ChannelMessage::lastIdForChannel($channelId),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }
}