<?php

namespace App\Services\Menu\Toasts;

use App\Models\Reminder;
use App\Models\User;
use App\Services\Menu\Contracts\MenuEdge;
use Illuminate\Support\Collection;

class ReminderDueTodayEdge implements MenuEdge
{
    public function getKey(): string
    {
        return 'reminders-controller:due-today-edge';
    }

    public function for($subject): Collection
    {
        return User::active()->where('id', $subject->user_id)->get();
    }

    public function title($subject, User $user): string
    {
        return 'یادآوری امروز: ' . $subject->title;
    }

    public function body($subject, User $user): string
    {
        return "یادآوری «{$subject->title}» امروز سررسید دارد.";
    }

    public function icon($subject, User $user): string
    {
        return 'notifications';
    }

    public function show($subject, User $user): bool
    {
        return $subject->completed_at === null
            && (!$subject->snoozed_until || $subject->snoozed_until->isPast())
            && $subject->due_at->isToday()
            && Reminder::channelEnabled($subject->channels, 'edge')
            && !$subject->hostTrashed();
    }

    public function url($subject): ?string
    {
        return Reminder::urlFor($subject->remindable_type, $subject->remindable_id);
    }

    public function triggers(): array
    {
        return [
            ['class' => Reminder::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ];
    }
}
