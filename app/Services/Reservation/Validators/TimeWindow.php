<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class TimeWindow implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $now = now();

        if ($context->start->isPast() && !$context->start->isSameMinute($now)) {
            ReservationError::PastBooking->throw();
        }

        $windowDays = $context->policies['window_days'] ?? null;
        $windowHours = $context->policies['window_hours'] ?? 0;

        if ($windowDays !== null && $context->start->isAfter($now->copy()->addDays((int)$windowDays))) {
            ReservationError::WindowExceeded->throw($windowDays);
        }

        if ($windowHours > 0 && $now->diffInHours($context->start, false) < $windowHours) {
            ReservationError::EarlyBooking->throw($windowHours);
        }
    }
}
