<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class ResourceActive implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->resource->status !== 'active') {
            ReservationError::ResourceInactive->throw();
        }
    }
}
