<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;

class MarkMessagesAsReadAction
{
    public function execute(int $contactId, int $viewerId): int
    {
        return Message::withoutTrashed()
            ->where('sender_id', $contactId)
            ->where('recipient_id', $viewerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
