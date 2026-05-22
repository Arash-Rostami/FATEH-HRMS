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

        $hasAllAccess = ($permissions['all'] ?? null) === true;
        $hasTypeAccess = ($permissions[$context->resource->type] ?? null) === true;

        if (!$hasAllAccess && !$hasTypeAccess) {
            ReservationError::NoPermission->throw();
        }
    }
}
