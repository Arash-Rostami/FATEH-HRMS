<?php

namespace App\Services\Menu\Indicators;

use App\Models\Message;
use App\Services\Menu\Contracts\MenuBadge;

class UnreadMessages implements MenuBadge
{
    public function getBody(): string
    {
        return 'شما پیام خوانده‌نشده دارید؛ برای مشاهده به بخش پیام‌رسان مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'contacts-controller';
    }

    public function getTitle(): string
    {
        return 'پیام خوانده‌نشده';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && Message::hasUnreadFor($user->id);
    }
}
