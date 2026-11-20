<?php

namespace App\Services\Menu\Indicators;

use App\Models\Ticket;
use App\Services\Menu\Contracts\MenuBadge;

class ThsBadge implements MenuBadge
{
    public function getBody(): string
    {
        return 'درخواستی منتظر تعیین مسئول، رسیدگی یا امتیاز شماست. برای پیگیری، بخش تیکتینگ را باز کنید.';
    }

    public function getKey(): string
    {
        return 'ths-controller';
    }

    public function getTitle(): string
    {
        return 'درخواستی منتظر اقدام شماست';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && Ticket::hasUnclosedActionFor($user->id);
    }
}
