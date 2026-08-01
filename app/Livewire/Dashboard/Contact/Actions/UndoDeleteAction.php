<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;

class UndoDeleteAction
{
    public function execute(array $lastDeleted): void
    {
        $userId = auth()->id();

        $restored = Message::withTrashed()
            ->where('sender_id', $userId)
            ->find($lastDeleted['original_id'] ?? null)
            ?->restore();

        if (!$restored) {
            if (($lastDeleted['sender_id'] ?? null) !== $userId) {
                return;
            }

            (new Message())->forceFill([
                'sender_id'    => $userId,
                'recipient_id' => $lastDeleted['recipient_id'],
                'body'         => $lastDeleted['body'],
                'attachments'  => $lastDeleted['attachments'] ?? [],
                'is_edited'    => $lastDeleted['is_edited'],
                'reply_to_id'  => $lastDeleted['reply_to_id'],
                'read_at'      => $lastDeleted['read_at'] ?? null,
                'created_at'   => $lastDeleted['created_at'],
                'updated_at'   => now(),
            ])->save();
        }
    }
}
