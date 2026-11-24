<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMessage;
use Illuminate\Support\Facades\DB;

class FindFirstUnreadChannelMessageAction
{
    public function execute(int $channelId, int $userId): ?int
    {
        $cursor = DB::table('channel_members')
            ->where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->value('last_read_message_id');

        if ($cursor === null) {
            return null;
        }

        $firstUnread = ChannelMessage::withoutTrashed()
            ->where('channel_id', $channelId)
            ->where('id', '>', $cursor)
            ->where('sender_id', '!=', $userId)
            ->min('id');

        return $firstUnread === null ? null : (int) $firstUnread;
    }
}
