<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;
use App\Models\ChannelMessage;

class UndoDeleteChannelMessageAction
{
    public function execute(array $lastDeleted): bool
    {
        $userId = auth()->id();

        $restored = ChannelMessage::withTrashed()
            ->where('sender_id', $userId)
            ->writableBy((int) $userId)
            ->find($lastDeleted['original_id'] ?? null)
            ?->restore();

        if (!$restored) {
            $channelId = $lastDeleted['channel_id'] ?? null;
            if (!$channelId || !Channel::writableBy((int) $userId)->whereKey($channelId)->exists()) {
                return false;
            }

            return (new ChannelMessage())->forceFill([
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

        return true;
    }
}