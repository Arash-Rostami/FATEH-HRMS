<?php

namespace App\Services\Menu\Indicators;

use App\Models\EnergyTest;
use App\Services\Menu\Contracts\MenuBadge;

class EnergyTestBadge implements MenuBadge
{
    public function getBody(): string
    {
        return 'شما هنوز پرسشنامه انرژی این ماه خود را تکمیل نکرده‌اید.';
    }

    public function getKey(): string
    {
        return 'energy-controller';
    }

    public function getTitle(): string
    {
        return 'ارزیابی انرژی ماهانه';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        return $user !== null && EnergyTest::canSubmit($user->id);
    }
}
