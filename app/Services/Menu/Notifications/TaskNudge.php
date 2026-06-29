<?php

namespace App\Services\Menu\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class TaskNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        return 'وظیفه جدیدی به شما ارجاع داده شده است؛ برای مشاهده به برد وظایف مراجعه کنید.';
    }

    public function for($subject)
    {
        if (!$subject->assigned_to) {
            return collect();
        }

        return User::active()->where('id', $subject->assigned_to)->get();
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
        return true;
    }

    public function title($subject, User $user): string
    {
        return 'وظیفه جدید: ' . $subject->title;
    }

    public function triggers(): array
    {
        return [
            ['class' => Task::class, 'on' => ['created', 'updated', 'deleted', 'forceDeleted'], 'subject' => null],
        ];
    }
}