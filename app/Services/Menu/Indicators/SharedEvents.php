<?php

namespace App\Services\Menu\Indicators;

use App\Models\Event;
use App\Models\EventShare;
use App\Services\Menu\Contracts\MenuBadge;
use App\Services\Menu\StateService;

class SharedEvents implements MenuBadge
{
    public function getBody(): string
    {
        return 'یکی از رویدادهای مشترک شما در ۲۴ ساعت آینده است؛ برای مشاهده به بخش تقویم مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'shared-events';
    }

    public function getTitle(): string
    {
        return 'رویداد مشترک نزدیک است';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null
            && !StateService::viewedToday('calendar')
            && (EventShare::hasImminentFor($user) || Event::hasImminentSharedFor($user));
    }
}