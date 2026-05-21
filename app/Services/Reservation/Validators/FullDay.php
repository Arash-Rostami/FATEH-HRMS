<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class FullDay implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->isFullDay && !($context->policies['allow_full_day'] ?? true)) {
            ReservationError::FullDayForbidden->throw();
        }
    }
}
