<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\Reservation\COntracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class UserConflict implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $exists = Reservation::where('user_id', $context->user->id)
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::Released->value])
            ->whereHas('resource', fn($q) => $q->where('type', $context->resource->type))
            ->when(
                $context->isFullDay,
                fn($q) => $q->whereDate('start_time', $context->start->toDateString()),
                fn($q) => $q->where('start_time', '<', $context->end)->where('end_time', '>', $context->start)
            )
            ->exists();

        if ($exists) {
            throw new Exception("شما در این بازه زمانی رزرو فعال دیگری از همین نوع دارید.");
        }
    }
}
