<?php

namespace App\Services\Menu\Indicators;

use App\Models\Task;
use App\Services\Menu\Contracts\MenuBadge;

class TasksTodo implements MenuBadge
{
    public function getBody(): string
    {
        return 'در برد وظایف شما وظیفه‌ای در ستون «انجام نشده» وجود دارد؛ برای مشاهده به برد وظایف مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'tasks-controller';
    }

    public function getTitle(): string
    {
        return 'وظیفه جدید در ستون انجام‌نشده';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && Task::getTodoCount($user->id) > 0;
    }
}