<?php

namespace App\Services\Menu\Indicators;

use App\Models\Post;
use App\Services\Menu\Contracts\MenuBadge;

class TodayPosts implements MenuBadge
{
    public function getBody(): string
    {
        return 'امروز پست جدیدی منتشر شده است؛ برای مشاهده به بخش اطلاعیه‌ها مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'posts-controller';
    }

    public function getTitle(): string
    {
        return 'پست جدید';
    }

    public function isActive(): bool
    {
        return Post::whereDate('created_at', now()->toDateString())->exists();
    }
}
