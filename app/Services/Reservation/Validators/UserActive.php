<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class UserActive implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if (!$context->user->isActive()) {
            ReservationError::UserInactive->throw();
        }
    }
}
