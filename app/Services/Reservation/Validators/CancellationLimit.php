<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use App\Services\Reservation\ValidationService;

class CancellationLimit implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $limit = $context->policies['max_cancel_count'] ?? null;
        if ($limit === null) return;

        $limit = max(0, (int)$limit);
        if ($limit === 0) return;

        $count = app(ValidationService::class)->cancelLimitStats($context->user->id, $context->resource->type, $context->policies)['count'];

        if ($count >= $limit) {
            ReservationError::CancelLimitBooking->throw();
        }
    }
}
