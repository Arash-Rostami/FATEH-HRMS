<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMessage;

class UndoDeleteChannelMessageAction
{
    public function execute(array $lastDeleted): void
    {
        $restored = ChannelMessage::withTrashed()
            ->find($lastDeleted['original_id'] ?? null)
            ?->restore();

        if (!$restored) {
            ChannelMessage::create([
                'channel_id'  => $lastDeleted['channel_id'],
                'sender_id'   => $lastDeleted['sender_id'],
                'body'        => $lastDeleted['body'],
                'attachments' => $lastDeleted['attachments'] ?? null,
                'is_edited'   => $lastDeleted['is_edited'],
                'reply_to_id' => $lastDeleted['reply_to_id'],
                'created_at'  => $lastDeleted['created_at'],
                'updated_at'  => now(),
            ]);
        }
    }
}