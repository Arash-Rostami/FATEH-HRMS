<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;

class UndoDeleteAction
{
    public function execute(array $lastDeleted): void
    {
        $restored = Message::withTrashed()
            ->find($lastDeleted['original_id'] ?? null)
            ?->restore();

        if (!$restored) {
            Message::create([
                'sender_id'    => $lastDeleted['sender_id'],
                'recipient_id' => $lastDeleted['recipient_id'],
                'body'         => $lastDeleted['body'],
                'is_edited'    => $lastDeleted['is_edited'],
                'reply_to_id'  => $lastDeleted['reply_to_id'],
                'created_at'   => $lastDeleted['created_at'],
                'updated_at'   => now(),
            ]);
        }
    }
}
