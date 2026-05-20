<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class ActiveLimit implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $max = $context->policies['max_per_user'] ?? $context->user->maximum;

        $count = Reservation::where('user_id', $context->user->id)
            ->whereHas('resource', fn($q) => $q->where('type', $context->resource->type))
            ->whereMonth('start_time', $context->start->month)
            ->whereYear('start_time', $context->start->year)
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::Released->value])
            ->toBase()->count();

        if ($count >= $max) {
            throw new Exception("سقف رزرو فعال شما برای این نوع به پایان رسیده است.");
        }
    }
}
