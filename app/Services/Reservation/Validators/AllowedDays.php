<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class AllowedDays implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $allowedDays = $context->policies['allowed_days'] ?? null;
        if (!is_array($allowedDays)) return;

        $day = strtolower($context->start->englishDayOfWeek);
        if (!in_array($day, $allowedDays)) {
            ReservationError::DayNotAllowed->throw($context->start->dayName);
        }
    }
}
