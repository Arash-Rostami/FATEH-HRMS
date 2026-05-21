<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class ActiveLimit implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $max = $context->policies['max_per_user'] ?? null;
        if ($max === null) return;

        $count = Reservation::where('user_id', $context->user->id)
            ->whereHas('resource', fn($q) => $q->where('type', $context->resource->type))
            ->whereMonth('start_time', $context->start->month)
            ->whereYear('start_time', $context->start->year)
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::Released->value])
            ->when($context->excludeId, fn($q) => $q->where('id', '!=', $context->excludeId))
            ->toBase()->count();

        if ($count >= $max) {
            ReservationError::ActiveLimitReached->throw();
        }
    }
}
