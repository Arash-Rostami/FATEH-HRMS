<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class BookingPermission implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $permissions = $context->user->booking;

        if (empty($permissions['all']) && empty($permissions[$context->resource->type])) {
            ReservationError::NoPermission->throw();
        }
    }
}

