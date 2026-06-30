<?php

namespace App\Services\Menu\Indicators;

use App\Models\DMS;
use App\Services\Menu\Contracts\MenuBadge;

class DmsBadge implements MenuBadge
{
    public function getBody(): string
    {
        return 'سندی در مدیریت اسناد نیازمند تأیید یا مطالعه شماست؛ برای مشاهده به مدیریت اسناد مراجعه کنید.';
    }

    public function getKey(): string
    {
        return 'dms-controller';
    }

    public function getTitle(): string
    {
        return 'سند نیازمند تأیید/مطالعه';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && DMS::hasPendingFor($user->id);
    }
}