<?php

namespace App\Services\Menu\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class TaskApprovalNudge implements MenuNudge
{
    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function body($subject, User $user): string
    {
        return "وظیفهٔ «{$subject->title}» در ستون انجام است و منتظر تأیید شماست.";
    }

    public function for($subject): Collection
    {
        if (!$subject->relationLoaded('project')) {
            $subject->loadMissing('project:id,settings,owner_id');
        }

        $ownerId = $subject->project?->owner_id;

        return $ownerId === null
            ? collect()
            : User::active()->where('id', $ownerId)->get();
    }

    public function getKey(): string
    {
        return 'tasks-controller:approval-nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        if (!$subject->relationLoaded('project')) {
            $subject->loadMissing('project:id,settings,owner_id');
        }

        return $subject->isPendingApproval();
    }

    public function title($subject, User $user): string
    {
        return $this->urgencyPrefix($subject) . 'وظیفهٔ منتظر تأیید: ' . $subject->title;
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

    private function urgencyPrefix($subject): string
    {
        $hoursPending = $subject->updated_at->diffInHours(now());

        return match (true) {
            $hoursPending > 48 => '⏰ فوری: ',
            $hoursPending > 24 => 'یادآوری: ',
            default => '',
        };
    }
}