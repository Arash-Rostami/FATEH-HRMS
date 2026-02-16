<?php

namespace App\Models\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait HasDateHelpers
{
    public function daysPassed(?string $date, int $ttlHours = 12): ?string
    {
        if (!$date) return null;
        $key = "days_passed_{$this->getKey()}_" . md5($date);
        return Cache::remember($key, now()->addHours($ttlHours), function () use ($date) {
            return Carbon::parse($date)->diffInDays(now()) . ' روز';
        });
    }

    public function daysUntil(?string $date, int $ttlHours = 12): ?string
    {
        if (!$date) return null;
        $key = "days_until_{$this->getKey()}_" . md5($date);
        return Cache::remember($key, now()->addHours($ttlHours), function () use ($date) {
            $target = Carbon::parse($date)->setYear(now()->year);
            if ($target->isPast()) $target->addYear();
            return $target->diffInDays(now()) . ' روز';
        });
    }
}
