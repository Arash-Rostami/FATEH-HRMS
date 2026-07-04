<?php

namespace App\Services\Menu\Indicators;

use App\Models\Post;
use App\Services\Menu\Contracts\MenuBadge;

class UnreadPosts implements MenuBadge
{
    public function getBody(): string
    {
        return 'شما اطلاعیه‌های خوانده‌نشده دارید؛ برای مشاهده به بخش اطلاعیه‌ها مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'posts-controller';
    }

    public function getTitle(): string
    {
        return 'اعلانات خوانده‌نشده';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && Post::hasUnreadFor($user->id);
    }
}