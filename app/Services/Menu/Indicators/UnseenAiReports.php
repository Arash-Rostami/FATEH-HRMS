<?php

namespace App\Services\Menu\Indicators;

use App\Models\AiReport;
use App\Services\Menu\Contracts\MenuBadge;

class UnseenAiReports implements MenuBadge
{
    public function getBody(): string
    {
        return 'گزارشی با شما به اشتراک گذاشته شده که هنوز آن را باز نکرده‌اید.';
    }

    public function getKey(): string
    {
        return 'ai-report-controller';
    }

    public function getTitle(): string
    {
        return 'گزارش ساز هوشمند دیده‌نشده';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && AiReport::query()->unseenBy($user->id)->exists();
    }
}
