<?php

namespace App\Services\Menu\Indicators;

use App\Models\Feed;
use App\Services\Menu\Contracts\MenuBadge;

class TodayFeeds implements MenuBadge
{
    public function getBody(): string
    {
        return 'امروز خبر جدیدی منتشر شده است؛ برای مشاهده به بخش اخبار مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'feeds';
    }

    public function getTitle(): string
    {
        return 'خبر جدید';
    }

    public function isActive(): bool
    {
        return Feed::postedToday();
    }
}