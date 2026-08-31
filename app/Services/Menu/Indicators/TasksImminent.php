<?php

namespace App\Services\Menu\Indicators;

use App\Models\Task;
use App\Services\Menu\Contracts\MenuBadge;

class TasksImminent implements MenuBadge
{
    public function getBody(): string
    {
        return 'یکی از وظایف شما سررسید گذشته یا نزدیک است؛ برای مشاهده به برد وظایف مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'tasks-deadline';
    }

    public function getTitle(): string
    {
        return 'وظیفهٔ نزدیک به سررسید';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return Task::query()
            ->forUser($user->id)
            ->whereNotIn('status', ['done', 'pending'])
            ->whereNotNull('deadline')
            ->where('deadline', '<=', now()->addDays(3)->endOfDay())
            ->get(['id', 'status', 'deadline', 'updated_at'])
            ->contains(fn(Task $task) => in_array($task->urgency_state['kind'], ['overdue', 'due'], true));
    }
}
