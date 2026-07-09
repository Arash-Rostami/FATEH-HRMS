<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;

class FocusMessageAction
{
    public function execute(int $userId, int $messageId, int $authId, int $loadedLimit): ?bool
    {
        if ($userId <= 0 || $messageId <= 0 || $authId <= 0) {
            return null;
        }

        $count = Message::withoutTrashed()
            ->where(fn($q) => $q
                ->where('sender_id', $authId)->where('recipient_id', $userId)
                ->orWhere(fn($q2) => $q2->where('sender_id', $userId)->where('recipient_id', $authId))
            )
            ->where('id', '>=', $messageId)
            ->orderBy('id')
            ->take($loadedLimit + 1)
            ->pluck('id')
            ->count();

        if ($count === 0) {
            return null;
        }

        return $count > $loadedLimit;
    }
}
