<?php

namespace App\Services\Menu\Indicators;

use App\Models\Task;
use App\Services\Menu\Contracts\MenuBadge;

class TasksPendingApproval implements MenuBadge
{
    public function getBody(): string
    {
        return 'در پروژه‌های تحت مدیریت شما وظیفه‌ای در ستون «انجام‌شده» منتظر تأیید است؛ برای بررسی به پروژه‌ها مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'tasks-pending-approval';
    }

    public function getTitle(): string
    {
        return 'وظیفهٔ منتظر تأیید';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && Task::query()
            ->where('status', 'done')
            ->whereNull('approved_at')
            ->whereHas('project', fn($q) => $q
                ->where('owner_id', $user->id)
                ->where('settings->requires_approval', true))
            ->exists();
    }
}