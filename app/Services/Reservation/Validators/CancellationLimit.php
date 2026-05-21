<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class CancellationLimit implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $limit = $context->policies['max_cancel_count'] ?? null;
        if ($limit === null) return;

        $limit = max(1, (int)$limit);

        $count = Reservation::where('user_id', $context->user->id)
            ->whereHas('resource', fn($q) => $q->where('type', $context->resource->type))
            ->where('status', ReservationStatus::CancelledUser->value)
            ->where(fn($q) => $q->whereNull('cancelled_at')->orWhere('cancelled_at', '>=', now()->subDays(30)))
            ->toBase()->count();

        if ($count >= $limit) {
            ReservationError::CancelLimitBooking->throw();
        }
    }
}
