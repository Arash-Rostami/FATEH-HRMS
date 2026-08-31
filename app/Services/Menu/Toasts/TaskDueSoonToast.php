<?php

namespace App\Services\Menu\Toasts;

use App\Models\Task;
use App\Models\User;
use App\Services\Menu\Contracts\MenuEdge;
use Illuminate\Support\Collection;

class TaskDueSoonToast implements MenuEdge
{
    public function getKey(): string
    {
        return 'tasks-controller:due-soon-edge';
    }

    public function for($subject): Collection
    {
        $ownerId = $subject->assigned_to ?? $subject->user_id;

        return User::active()->where('id', $ownerId)->get();
    }

    public function title($subject, User $user): string
    {
        return 'وظیفهٔ نزدیک به سررسید: ' . $subject->title;
    }

    public function body($subject, User $user): string
    {
        return "وظیفهٔ «{$subject->title}» کمتر از ۲۴ ساعت دیگر مهلت دارد.";
    }

    public function icon($subject, User $user): string
    {
        return 'schedule';
    }

    public function show($subject, User $user): bool
    {
        if (!$subject->deadline || in_array($subject->status, ['done', 'pending'], true) || $subject->deadline->isPast()) {
            return false;
        }

        return now()->diffInHours($subject->deadline) <= 24;
    }

    public function url($subject): ?string
    {
        return route('tasks', ['open' => $subject->getKey()]);
    }

    public function triggers(): array
    {
        return [
            ['class' => Task::class, 'on' => ['created', 'updated'], 'subject' => null],
        ];
    }
}
