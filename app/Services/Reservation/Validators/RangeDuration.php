<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class RangeDuration implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if (!$context->isRange()) {
            return;
        }

        $max = $context->policies['max_range_days'] ?? null;
        if ($max === null) {
            return;
        }

        if ($context->start->diffInDays($context->end) > (int) $max) {
            ReservationError::RangeTooLong->throw($max);
        }
    }
}