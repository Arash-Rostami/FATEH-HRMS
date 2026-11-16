<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMessage;
use App\Services\Messaging\PollDigestService;
use App\Services\ProjectTask\ToggleReplyReactionAction;
use Illuminate\Support\Facades\DB;

class ToggleChannelMessageReactionAction
{
    public function execute(int $messageId, string $emoji, int $userId): bool
    {
        abort_unless(auth()->user()?->isActive(), 403);
        abort_unless(in_array($emoji, ToggleReplyReactionAction::ALLOWED_EMOJI, true), 422);

        $channelId = DB::transaction(function () use ($messageId, $emoji, $userId): ?int {
            $message = ChannelMessage::withoutTrashed()
                ->whereKey($messageId)
                ->whereHas('channel.memberUsers', fn ($q) => $q->whereKey($userId))
                ->lockForUpdate()
                ->first();

            $message?->toggleReaction($userId, $emoji);

            return $message ? (int) $message->channel_id : null;
        });

        if ($channelId === null) {
            return false;
        }

        PollDigestService::bump("ch:rv:{$channelId}");

        return true;
    }
}
