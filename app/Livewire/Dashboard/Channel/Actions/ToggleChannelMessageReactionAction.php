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
                ->whereHas('channel.memberUsers', fn($q) => $q->where('users.id', $userId))
                ->lockForUpdate()
                ->first();

            if (!$message) {
                return null;
            }

            $reactions = collect($message->reactions ?? []);
            $same = $reactions->contains(
                fn(array $r) => (int) ($r['user_id'] ?? 0) === $userId && ($r['emoji'] ?? null) === $emoji
            );

            $reactions = $reactions->reject(fn(array $r) => (int) ($r['user_id'] ?? 0) === $userId);

            if (!$same) {
                $reactions->push(['user_id' => $userId, 'emoji' => $emoji]);
            }

            DB::table('channel_messages')->where('id', $messageId)->update([
                'reactions' => json_encode($reactions->values()->all(), JSON_UNESCAPED_UNICODE),
            ]);

            return (int) $message->channel_id;
        });

        if ($channelId === null) {
            return false;
        }

        PollDigestService::bump("ch:rv:{$channelId}");

        return true;
    }
}
