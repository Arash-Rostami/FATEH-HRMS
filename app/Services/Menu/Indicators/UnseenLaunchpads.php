<?php

namespace App\Services\Menu\Indicators;

use App\Models\Launchpad;
use App\Services\Menu\Contracts\MenuBadge;

class UnseenLaunchpads implements MenuBadge
{
    public function getBody(): string
    {
        return 'پیشخوان برنامه‌ای با شما به اشتراک گذاشته شده که هنوز آن را باز نکرده‌اید.';
    }

    public function getKey(): string
    {
        return 'launchpad-controller';
    }

    public function getTitle(): string
    {
        return 'پیشخوان برنامه‌های دیده‌نشده';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && Launchpad::query()->unseenBy((int) $user->id)->exists();
    }
}
