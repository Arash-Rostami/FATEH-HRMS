<?php

namespace App\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Models\Reply;

class SaveEditReplyAction
{
    public const EDIT_TIME_LIMIT = 600;

    public function execute(int $replyId, int $userId, string $body): bool
    {
        $body = trim($body);
        if ($body === '') {
            return false;
        }

        $reply = Reply::query()
            ->whereKey($replyId)
            ->where('user_id', $userId)
            ->whereIn('type', [TaskActivityType::Comment, TaskActivityType::Attachment])
            ->first();

        if (!$reply || $reply->created_at->diffInSeconds(now()) > self::EDIT_TIME_LIMIT) {
            return false;
        }

        $reply->update([
            'body' => $body,
            'payload' => [...($reply->payload ?? []), 'edited_at' => now()->toIso8601String()],
        ]);

        if ($projectId = $reply->projectId()) {
            ProjectHeartbeat::bump($projectId, 'activity');
        }

        return true;
    }
}
