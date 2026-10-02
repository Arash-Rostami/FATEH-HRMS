<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;
use Illuminate\Support\Facades\DB;

class FocusMessageAction
{
    public function execute(int $userId, int $messageId, int $authId, int $loadedLimit): ?bool
    {
        if ($userId <= 0 || $messageId <= 0 || $authId <= 0) {
            return null;
        }

        $direction = fn(int $senderId, int $recipientId) => Message::query()
            ->from(DB::raw('`messages` FORCE INDEX (`idx_sent_covering`)'))
            ->where('sender_id', $senderId)
            ->where('recipient_id', $recipientId);

        $count = $direction($authId, $userId)->where('id', '>=', $messageId)->oldest('id')->take($loadedLimit + 1)->pluck('id')
            ->merge($direction($userId, $authId)->where('id', '>=', $messageId)->oldest('id')->take($loadedLimit + 1)->pluck('id'))
            ->count();

        if ($count === 0) {
            return null;
        }

        return $count > $loadedLimit;
    }
}
