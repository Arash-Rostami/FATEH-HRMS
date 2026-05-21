<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class BookingPermission implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $raw = is_array($context->user->booking)
            ? $context->user->booking
            : json_decode($context->user->booking ?? '[]', true);

        $permissions = array_column($raw ?? [], 'value', 'key');

        if (empty($permissions['all']) && empty($permissions[$context->resource->type])) {
            ReservationError::NoPermission->throw();
        }
    }
}
