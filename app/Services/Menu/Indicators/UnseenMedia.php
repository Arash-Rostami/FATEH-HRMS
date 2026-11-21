<?php

namespace App\Services\Menu\Indicators;

use App\Models\Media;
use App\Services\Menu\Contracts\MenuBadge;

class UnseenMedia implements MenuBadge
{
    public function getBody(): string
    {
        return 'رسانهٔ جدیدی منتشر شده که هنوز آن را پخش نکرده‌اید.';
    }

    public function getKey(): string
    {
        return 'media-controller';
    }

    public function getTitle(): string
    {
        return 'رسانهٔ پخش‌نشده';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && Media::query()->newFor((int) $user->id)->exists();
    }
}
