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
        $max = $context->user->bookingMax($context->resource->type) ?? $context->policies['max_per_user'] ?? null;
        if ($max === null) return;

        $count = Reservation::where('user_id', $context->user->id)
            ->whereHas('resource', fn($q) => $q->where('type', $context->resource->type))
            ->whereBetween('start_time', jalaliMonthBounds($context->start))
            ->held()
            ->when($context->excludeId, fn($q) => $q->where('id', '!=', $context->excludeId))
            ->toBase()->count();

        if ($count >= $max && !$this->lastMinute($context)) {
            ReservationError::ActiveLimitReached->throw();
        }
    }

    private function lastMinute(BookingContext $context): bool
    {
        $hours = $context->policies['last_minute_hours'] ?? null;

        return $hours !== null && $context->start->lte(now()->addHours((int) $hours));
    }
}
