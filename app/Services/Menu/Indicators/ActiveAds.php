<?php

namespace App\Services\Menu\Indicators;

use App\Models\Ad;
use App\Services\Menu\Contracts\MenuBadge;

class ActiveAds implements MenuBadge
{
    public function getBody(): string
    {
        return 'در بخش آگهی‌ها مورد فعالی وجود دارد که باعث نمایش نشان هشدار در منو شده است.';
    }

    public function getKey(): string
    {
        return 'ads-controller';
    }

    public function getTitle(): string
    {
        return 'آگهی‌های فعال نیاز به بررسی دارند';
    }

    public function isActive(): bool
    {
        return Ad::active()->exists();
    }
}
