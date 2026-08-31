<?php

namespace App\Services\ProjectTask;

use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ToggleReplyReactionAction
{
    private const ALLOWED_EMOJI = ['👍', '❤️', '😂', '🎉', '👀'];

    public function execute(int $replyId, string $emoji, int $userId): array
    {
        abort_unless(in_array($emoji, self::ALLOWED_EMOJI, true), 422);

        return DB::transaction(function () use ($replyId, $emoji, $userId) {
            $reply = Reply::query()->whereKey($replyId)->lockForUpdate()->firstOrFail();

            abort_unless($this->canReact($reply, $userId), 403);

            $reactions = collect($reply->reactions ?? []);
            $existingIndex = $reactions->search(
                fn(array $r) => (int) ($r['user_id'] ?? null) === $userId && ($r['emoji'] ?? null) === $emoji
            );

            if ($existingIndex !== false) {
                $reactions->forget($existingIndex);
            } else {
                $reactions->push(['user_id' => $userId, 'emoji' => $emoji]);
            }

            $reply->forceFill(['reactions' => $reactions->values()->all()])->save();

            $this->bumpHeartbeat($reply);

            return $reply->reactions;
        });
    }

    private function canReact(Reply $reply, int $userId): bool
    {
        return match ($reply->repliable_type) {
            Project::class => $reply->repliable && $reply->repliable->isVisibleTo(User::findOrFail($userId)),
            Task::class => $this->canReactToTask($reply->repliable, $userId),
            default => false,
        };
    }

    private function canReactToTask(?Task $task, int $userId): bool
    {
        if (!$task) {
            return false;
        }

        if ($task->user_id === $userId
            || $task->assigned_to === $userId
            || in_array($userId, $task->detail?->collaborators ?? [], true)) {
            return true;
        }

        return $task->project_id && $task->project?->isVisibleTo(User::findOrFail($userId));
    }

    private function bumpHeartbeat(Reply $reply): void
    {
        $projectId = $reply->projectId();

        if ($projectId) {
            ProjectHeartbeat::bump($projectId, 'activity');
        }
    }
}
