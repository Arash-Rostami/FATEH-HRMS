<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use Illuminate\Support\Facades\DB;

class MarkChannelReadAction
{
    public function execute(int $channelId, int $userId): void
    {
        $lastId = ChannelMessage::lastIdForChannel($channelId);

        if ($lastId === null) {
            return;
        }

        DB::transaction(function () use ($channelId, $userId, $lastId) {
            ChannelMember::where('channel_id', $channelId)
                ->where('user_id', $userId)
                ->where(fn($q) => $q->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $lastId))
                ->update(['last_read_message_id' => $lastId]);
        });
    }
}