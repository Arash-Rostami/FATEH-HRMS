<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMessage;
use App\Services\Messaging\MessageEnhancer;

class EnhanceChannelMessageAction
{
    private const MODULE = ['config' => 'ai.channel.enhance', 'client' => 'channel', 'stem' => 'channel', 'quota' => ChannelMessage::class];

    public function __construct(private MessageEnhancer $enhancer)
    {
    }

    public function execute(int $messageId, int $userId, string $text, int $editTimeLimit): array
    {
        abort_unless(channelAiOn(), 404);

        $user = auth()->user();

        abort_unless($user?->isActive() && (int) $user->id === $userId, 403);

        $text = trim($text);

        if ($text === '') {
            return $this->enhancer->fail(self::MODULE, $messageId, $userId, 'question');
        }

        if (mb_strlen($text) > (int) config('ai.channel.enhance.input_max')) {
            return $this->enhancer->fail(self::MODULE, $messageId, $userId, 'long');
        }

        $message = ChannelMessage::withoutTrashed()
            ->whereKey($messageId)
            ->where('sender_id', $userId)
            ->writableBy($userId)
            ->first(['id', 'created_at']);

        if (!$message || $message->created_at->diffInSeconds(now()) > $editTimeLimit) {
            return $this->enhancer->fail(self::MODULE, $messageId, $userId, 'expired');
        }

        return $this->enhancer->enhance(self::MODULE, $messageId, $userId, $text);
    }

    public function meter(int $userId): string
    {
        return $this->enhancer->meter(ChannelMessage::class, $userId);
    }
}
