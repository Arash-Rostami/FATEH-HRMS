<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use Illuminate\Support\Facades\DB;

class ToggleMessagePinAction
{
    public function execute(int $peerId, int $messageId, int $userId, bool $onlyUnpin = false): bool
    {
        abort_unless(auth()->user()?->isActive(), 403);

        $pin = fn () => Message::pinPair($userId, $peerId);

        if ($onlyUnpin || (int) $pin()->value('message_id') === $messageId) {
            $pin()->where('message_id', $messageId)->delete();
        } elseif ($this->canPin($messageId, $userId, $peerId)) {
            DB::table('message_pins')->upsert(
                [
                    'user_low_id' => min($userId, $peerId),
                    'user_high_id' => max($userId, $peerId),
                    'message_id' => $messageId,
                ],
                ['user_low_id', 'user_high_id'],
                ['message_id']
            );
        } else {
            return false;
        }

        PollDigestService::bump(Message::pairVersionKey($userId, $peerId));

        return true;
    }

    private function canPin(int $messageId, int $userId, int $peerId): bool
    {
        return User::visibleOnBoard()->whereKey($peerId)->exists()
            && Message::withoutTrashed()
                ->whereKey($messageId)
                ->where(fn ($q) => $q
                    ->where(fn ($q) => $q->where('sender_id', $userId)->where('recipient_id', $peerId))
                    ->orWhere(fn ($q) => $q->where('sender_id', $peerId)->where('recipient_id', $userId))
                )
                ->exists();
    }
}
