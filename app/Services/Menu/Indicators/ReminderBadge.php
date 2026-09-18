<?php

namespace App\Services\Menu\Indicators;

use App\Models\Reminder;
use App\Services\Menu\Contracts\MenuBadge;

class ReminderBadge implements MenuBadge
{
    public function getBody(): string
    {
        return 'یادآوری سررسیدشده‌ای برای شما ثبت شده است؛ برای مشاهده به بخش یادآوری‌ها مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'reminders-controller';
    }

    public function getTitle(): string
    {
        return 'یادآوری نیازمند اقدام';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return Reminder::dueBadgeCount($user->id) > 0;
    }
}
