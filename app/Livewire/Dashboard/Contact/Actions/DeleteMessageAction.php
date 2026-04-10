<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;

class DeleteMessageAction
{
    public function execute(int $messageId): ?array
    {
        $message = Message::withoutTrashed()
            ->where('id', $messageId)
            ->where('sender_id', auth()->id())
            ->first();

        if (!$message) return null;

        $snapshot = [
            'sender_id'    => $message->sender_id,
            'recipient_id' => $message->recipient_id,
            'body'         => $message->body,
            'is_edited'    => (bool) $message->is_edited,
            'reply_to_id'  => $message->reply_to_id,
            'created_at'   => $message->created_at->toISOString(),
            'original_id'  => (int) $message->id,
        ];

        $message->delete();

        return $snapshot;
    }
}
