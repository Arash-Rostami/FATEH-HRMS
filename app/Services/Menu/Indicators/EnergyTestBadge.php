<?php

namespace App\Services\Menu\Indicators;

use App\Models\EnergyTest;
use App\Services\Menu\Contracts\MenuBadge;
use Morilog\Jalali\Jalalian;

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

        if ($user === null) {
            return false;
        }

        $now = Jalalian::now();
        $startOfMonth = (new Jalalian($now->getYear(), $now->getMonth(), 1))->toCarbon();

        return !EnergyTest::where('user_id', $user->id)
            ->where('completed_at', '>=', $startOfMonth)
            ->exists();
    }
}
