<?php

namespace App\Services\Menu\Notifications;

use App\Models\Report;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class ReportNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        return 'گزارش جدیدی آپلود شده است؛ لطفاً بررسی نمایید.';
    }

    public function for($subject)
    {
        return User::active()->get();
    }

    public function getKey(): string
    {
        return 'reports-controller:nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return $subject->active;
    }

    public function title($subject, User $user): string
    {
        return 'گزارش جدید از ' . ($subject->department?->displayLabel() ?? 'سازمان') . ': ' . $subject->title;
    }

    public function triggers(): array
    {
        return [
            ['class' => Report::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ];
    }
}
