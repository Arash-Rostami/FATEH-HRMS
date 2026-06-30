<?php

namespace App\Services\Menu\Indicators;

use App\Models\Ticket;
use App\Services\Menu\Contracts\MenuBadge;

class ThsBadge implements MenuBadge
{
    public function getBody(): string
    {
        return 'تیکت باز یا در حال بررسیِ مربوط به شما وجود دارد؛ برای مشاهده به بخش تیکتینگ مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'ths-controller';
    }

    public function getTitle(): string
    {
        return 'تیکت نیازمند اقدام';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && Ticket::hasUnclosedActionFor($user->id);
    }
}