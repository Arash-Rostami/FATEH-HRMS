<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class Duration implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->start->gte($context->end)) {
            ReservationError::InvalidTimeRange->throw();
        }

        if ($context->isFullDay) return;

        $minutes = $context->start->diffInMinutes($context->end);
        $min = $context->policies['min_duration_minutes'] ?? null;
        $max = $context->policies['max_duration_minutes'] ?? null;

        if ($min !== null && $minutes < (int) $min) ReservationError::TooShort->throw($min);
        if ($max !== null && $minutes > (int) $max) ReservationError::TooLong->throw($max);
    }
}
