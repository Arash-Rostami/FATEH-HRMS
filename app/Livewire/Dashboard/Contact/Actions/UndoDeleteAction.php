<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;
use App\Models\User;

class UndoDeleteAction
{
    public function execute(array $lastDeleted): bool
    {
        $userId = auth()->id();

        $restored = Message::withTrashed()
            ->where('sender_id', $userId)
            ->toActivePeer()
            ->find($lastDeleted['original_id'] ?? null)
            ?->restore();

        if ($restored) {
            return true;
        }

        if (($lastDeleted['sender_id'] ?? null) !== $userId || !User::visibleOnBoard()->whereKey($lastDeleted['recipient_id'] ?? 0)->exists()) {
            return false;
        }

        return (new Message)->forceFill([
            'sender_id' => $userId,
            'recipient_id' => $lastDeleted['recipient_id'],
            'body' => $lastDeleted['body'],
            'attachments' => $lastDeleted['attachments'] ?? [],
            'reactions' => $lastDeleted['reactions'] ?? null,
            'is_edited' => $lastDeleted['is_edited'],
            'reply_to_id' => $lastDeleted['reply_to_id'],
            'read_at' => $lastDeleted['read_at'] ?? null,
            'created_at' => $lastDeleted['created_at'],
            'updated_at' => now(),
        ])->save();
    }
}
