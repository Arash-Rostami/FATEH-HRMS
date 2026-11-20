<?php

namespace App\Services\Menu\Indicators;

use App\Models\Task;
use App\Services\Menu\Contracts\MenuBadge;

class TasksImminent implements MenuBadge
{
    public function getBody(): string
    {
        return 'مهلت یکی از کارهای شما گذشته یا نزدیک است. برای پیگیری، برد وظایف را باز کنید.';
    }

    public function getKey(): string
    {
        return 'tasks-deadline';
    }

    public function getTitle(): string
    {
        return 'مهلت کاری نزدیک است یا گذشته';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return Task::query()
            ->ownedBy($user->id)
            ->whereNotIn('status', ['done', 'pending'])
            ->whereNotNull('deadline')
            ->where('deadline', '<=', now()->addDays(3)->endOfDay())
            ->with('detail:id,task_id,state')
            ->get(['id', 'status', 'deadline', 'updated_at'])
            ->contains(fn(Task $task) => in_array($task->urgency_state['kind'], ['overdue', 'due'], true));
    }
}
