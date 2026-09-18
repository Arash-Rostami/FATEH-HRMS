<?php

namespace App\Services\Menu\Notifications;

use App\Models\Reminder;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class ReminderOverdueNudge implements MenuNudge
{
    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function body($subject, User $user): string
    {
        return "یادآوری «{$subject->title}» سررسید شده است.";
    }

    public function for($subject): Collection
    {
        return User::active()->where('id', $subject->user_id)->get();
    }

    public function getKey(): string
    {
        return 'reminders-controller:due-nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return $subject->completed_at === null
            && (!$subject->snoozed_until || $subject->snoozed_until->isPast())
            && $subject->due_at->lte(now()->endOfDay())
            && Reminder::channelEnabled($subject->channels, 'nudge')
            && !$subject->hostTrashed();
    }

    public function title($subject, User $user): string
    {
        return 'یادآوری: ' . $subject->title;
    }

    public function triggers(): array
    {
        return [
            ['class' => Reminder::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ];
    }

    public function url($subject): ?string
    {
        return Reminder::urlFor($subject->remindable_type, $subject->remindable_id);
    }
}
