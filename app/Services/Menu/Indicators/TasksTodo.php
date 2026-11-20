<?php

namespace App\Services\Menu\Indicators;

use App\Models\Task;
use App\Services\Menu\Contracts\MenuBadge;

class TasksTodo implements MenuBadge
{
    public function getBody(): string
    {
        return 'در ستون «انجام‌نشده» کاری دارید. برای پیگیری، برد وظایف را باز کنید.';
    }

    public function getKey(): string
    {
        return 'tasks-controller';
    }

    public function getTitle(): string
    {
        return 'کاری در ستون انجام‌نشده دارید';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && Task::getTodoCount($user->id) > 0;
    }
}
