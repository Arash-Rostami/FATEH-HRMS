<?php

namespace App\Services\Menu\Notifications;

use App\Enums\TaskActivityType;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class TaskNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        if ($this->isCompletionReply($subject)) {
            return 'وظیفهٔ شما به پایان رسید؛ برای مشاهده به برد وظایف مراجعه کنید.';
        }

        $ownerId = $subject->assigned_to ?? $subject->user_id;

        if ((int)$user->id === (int)$ownerId) {
            return 'وظیفه جدیدی به شما ارجاع داده شده است؛ برای مشاهده به برد وظایف مراجعه کنید.';
        }

        return 'پاسخ جدیدی برای وظیفه شما ثبت شده است؛ برای مشاهده به برد وظایف مراجعه کنید.';
    }

    private function isCompletionReply($subject): bool
    {
        $latestReply = $subject->latestReply();

        return $latestReply?->type === TaskActivityType::StatusChange
            && ($latestReply->payload['to'] ?? null) === 'done';
    }

    public function for($subject): Collection
    {
        $recipients = collect();
        $ownerId = $subject->assigned_to ?? $subject->user_id;

        if ($ownerId && ($owner = User::active()->where('id', $ownerId)->first())) {
            $recipients->push($owner);
        }

        $otherPartyIds = $subject->otherReplyParticipants([$subject->user_id, $subject->assigned_to]);

        if ($otherPartyIds) {
            $recipients = $recipients->merge(User::active()->whereIn('id', $otherPartyIds)->get());
        }

        $recipients = $recipients->merge($this->collaboratorRecipients($subject));

        return $recipients->unique('id')->values();
    }

    private function collaboratorRecipients(Task $task): Collection
    {
        if (!$task->latestReply()) {
            return collect();
        }

        $collaboratorIds = collect($task->detail?->collaborators ?? [])->filter();

        if ($collaboratorIds->isEmpty()) {
            return collect();
        }

        return User::active()->whereIn('id', $collaboratorIds)->get();
    }

    public function getKey(): string
    {
        return 'tasks-controller:nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        $latestReply = $subject->latestReply();

        if ($latestReply && (int)$latestReply->user_id === $user->id) {
            $ownerId = $subject->assigned_to ?? $subject->user_id;
            return (int)$user->id === (int)$ownerId;
        }

        return true;
    }

    public function title($subject, User $user): string
    {
        if ($this->isCompletionReply($subject)) {
            return 'وظیفه تکمیل شد: ' . $subject->title;
        }

        $ownerId = $subject->assigned_to ?? $subject->user_id;

        return (int)$user->id === (int)$ownerId
            ? 'وظیفه جدید: ' . $subject->title
            : 'پاسخ جدید: ' . $subject->title;
    }

    public function triggers(): array
    {
        return [
            ['class' => Task::class, 'on' => ['created', 'updated', 'deleted', 'restored', 'forceDeleted'], 'subject' => null],
            ['class' => Reply::class, 'on' => ['created'], 'subject' => fn(Reply $reply) =>
                $reply->repliable_type === Task::class ? $reply->repliable : null],
        ];
    }

    public function url($subject): ?string
    {
        return route('tasks', ['open' => $subject->getKey()]);
    }
}
