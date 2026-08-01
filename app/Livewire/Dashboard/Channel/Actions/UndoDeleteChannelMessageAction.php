<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMember;
use App\Models\ChannelMessage;

class UndoDeleteChannelMessageAction
{
    public function execute(array $lastDeleted): void
    {
        $userId = auth()->id();

        $restored = ChannelMessage::withTrashed()
            ->where('sender_id', $userId)
            ->find($lastDeleted['original_id'] ?? null)
            ?->restore();

        if (!$restored) {
            $channelId = $lastDeleted['channel_id'] ?? null;
            if (!$channelId || !ChannelMember::where('channel_id', $channelId)->where('user_id', $userId)->exists()) {
                return;
            }

            (new ChannelMessage())->forceFill([
                'channel_id'  => $channelId,
                'sender_id'   => $userId,
                'body'        => $lastDeleted['body'],
                'attachments' => $lastDeleted['attachments'] ?? null,
                'is_edited'   => $lastDeleted['is_edited'],
                'reply_to_id' => $lastDeleted['reply_to_id'],
                'created_at'  => $lastDeleted['created_at'],
                'updated_at'  => now(),
            ])->save();
        }
    }
}