<?php

namespace App\Models\Traits;

use Carbon\Carbon;

trait HasDateHelpers
{
    public function daysPassed(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        return round(Carbon::parse($date)->diffInDays(now()));
    }

    public function daysUntil(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        $target = Carbon::parse($date)->setYear(now()->year);

        if ($target->isPast()) {
            $target->addYear();
        }

        return abs(round($target->diffInDays(now())));
    }
}
