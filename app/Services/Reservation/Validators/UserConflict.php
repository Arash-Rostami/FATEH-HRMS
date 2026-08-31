<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class UserConflict implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->isRange()) return;

        $exists = Reservation::where('user_id', $context->user->id)
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::Released->value])
            ->whereHas('resource', fn($q) => $q->where('type', $context->resource->type))
            ->when($context->excludeId, fn($q) => $q->where('id', '!=', $context->excludeId))
            ->where(fn($query) => $query
                ->where(fn($q) => $q->where('start_time', '<', $context->end)->where('end_time', '>', $context->start))
                ->orWhere(fn($q) => $q->whereNull('start_time')
                    ->where('created_at', '>=', $context->start->copy()->startOfDay())
                    ->where('created_at', '<=', $context->end->copy()->endOfDay()))
            )
            ->exists();

        if ($exists) {
            ReservationError::TimeConflict->throw();
        }
    }
}
