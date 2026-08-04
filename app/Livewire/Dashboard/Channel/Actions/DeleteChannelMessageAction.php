<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMessage;

class DeleteChannelMessageAction
{
    public function execute(int $messageId, int $editTimeLimit): array|false
    {
        $message = ChannelMessage::withoutTrashed()
            ->where('id', $messageId)
            ->where('sender_id', auth()->id())
            ->first();

        if (!$message || $message->created_at->diffInSeconds(now()) > $editTimeLimit) {
            return false;
        }

        $snapshot = [
            'channel_id'  => $message->channel_id,
            'sender_id'   => $message->sender_id,
            'body'        => $message->body,
            'attachments' => $message->attachments,
            'is_edited'   => (bool) $message->is_edited,
            'reply_to_id' => $message->reply_to_id,
            'created_at'  => $message->created_at->toDateTimeString(),
            'original_id' => (int) $message->id,
        ];

        $message->delete();

        return $snapshot;
    }
}