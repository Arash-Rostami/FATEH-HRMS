<?php

namespace App\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Models\Reply;

class DeleteReplyAction
{
    public const EDIT_TIME_LIMIT = 600;

    public function execute(int $replyId, int $userId): bool
    {
        $reply = Reply::query()
            ->whereKey($replyId)
            ->where('user_id', $userId)
            ->whereIn('type', [TaskActivityType::Comment, TaskActivityType::Attachment])
            ->first();

        if (!$reply || $reply->created_at->diffInSeconds(now()) > self::EDIT_TIME_LIMIT) {
            return false;
        }

        $projectId = $reply->projectId();

        $reply->delete();

        if ($projectId) {
            ProjectHeartbeat::bump($projectId, 'activity');
        }

        return true;
    }
}
