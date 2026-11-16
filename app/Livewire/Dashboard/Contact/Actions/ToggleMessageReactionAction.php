<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use App\Services\ProjectTask\ToggleReplyReactionAction;
use Illuminate\Support\Facades\DB;

class ToggleMessageReactionAction
{
    public function execute(int $messageId, string $emoji, int $userId): bool
    {
        abort_unless(auth()->user()?->isActive(), 403);
        abort_unless(in_array($emoji, ToggleReplyReactionAction::ALLOWED_EMOJI, true), 422);

        $pair = DB::transaction(function () use ($messageId, $emoji, $userId): ?array {
            $message = Message::withoutTrashed()
                ->select(['id', 'sender_id', 'recipient_id', 'reactions'])
                ->whereKey($messageId)
                ->where(fn($q) => $q->where('sender_id', $userId)->orWhere('recipient_id', $userId))
                ->lockForUpdate()
                ->first();

            if (!$message) {
                return null;
            }

            $peerId = (int) ((int) $message->sender_id === $userId ? $message->recipient_id : $message->sender_id);

            if (!User::visibleOnBoard()->whereKey($peerId)->exists()) {
                return null;
            }

            $message->toggleReaction($userId, $emoji);

            return [min($userId, $peerId), max($userId, $peerId)];
        });

        if ($pair === null) {
            return false;
        }

        PollDigestService::bump(Message::pairVersionKey($pair[0], $pair[1]));

        return true;
    }
}
