<?php

namespace App\Services\Menu\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class TaskOverdueNudge implements MenuNudge
{
    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function body($subject, User $user): string
    {
        return "وظیفهٔ «{$subject->title}» از سررسید خود گذشته است.";
    }

    public function for($subject): Collection
    {
        $ownerId = $subject->assigned_to ?? $subject->user_id;

        return User::active()->where('id', $ownerId)->get();
    }

    public function getKey(): string
    {
        return 'tasks-controller:overdue-nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return $subject->urgency_state['kind'] === 'overdue';
    }

    public function title($subject, User $user): string
    {
        return 'وظیفهٔ سررسید گذشته: ' . $subject->title;
    }

    public function triggers(): array
    {
        return [
            ['class' => Task::class, 'on' => ['updated'], 'subject' => null],
        ];
    }

    public function url($subject): ?string
    {
        return route('tasks', ['open' => $subject->getKey()]);
    }
}
