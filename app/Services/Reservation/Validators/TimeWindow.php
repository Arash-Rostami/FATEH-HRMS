<?php

namespace App\Services\Reservation\Validators;

use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class TimeWindow implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $windowDays = $context->policies['window_days'] ?? 21;
        $windowHours = $context->policies['window_hours'] ?? 0;

        if ($context->start->isAfter(now()->addDays($windowDays))) {
            throw new Exception("تاریخ انتخابی خارج از بازه مجاز (حداکثر {$windowDays} روز آینده) است.");
        }

        if ($windowHours > 0 && $context->start->isSameDay(now()) && now()->diffInHours($context->start, false) < $windowHours) {
            throw new Exception("رزرو باید حداقل {$windowHours} ساعت قبل از زمان شروع ثبت شود.");
        }
    }
}
