<?php

namespace App\Services\Menu\Indicators;

use App\Models\Profile;
use App\Services\Menu\Contracts\MenuBadge;

class SpecialDays implements MenuBadge
{
    public function getBody(): string
    {
        return 'امروز تولد یا سالگرد یکی از همکاران است؛ برای مشاهده به تقویم مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'special-days';
    }

    public function getTitle(): string
    {
        return 'مناسبت امروز';
    }

    public function isActive(): bool
    {
        $now = now();

        return Profile::query()
            ->whereNotIn('employment_status', ['terminated'])
            ->where(function ($q) use ($now) {
                $q->where(fn($q1) => $q1->whereMonth('birthdate', $now->month)->whereDay('birthdate', $now->day))
                    ->orWhere(fn($q2) => $q2->whereMonth('start_date', $now->month)->whereDay('start_date', $now->day));
            })
            ->exists();
    }
}