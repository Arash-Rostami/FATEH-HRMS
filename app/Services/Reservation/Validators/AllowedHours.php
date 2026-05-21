<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Carbon\Carbon;

class AllowedHours implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->isFullDay) return;

        $allowedHours = $context->policies['allowed_hours'] ?? null;
        if (empty($allowedHours)) return;

        $startTime = Carbon::parse($allowedHours['start'] ?? '00:00')->format('H:i:s');
        $endTime   = Carbon::parse($allowedHours['end']   ?? '23:59')->format('H:i:s');

        $startFormat = $context->start->format('H:i:s');
        $endFormat   = $context->end->format('H:i:s');

        if ($startTime <= $endTime) {
            if ($startFormat < $startTime || $endFormat > $endTime) {
                ReservationError::HourNotAllowed->throw($allowedHours['start'], $allowedHours['end']);
            }
        } else {
            $isStartValid = $startFormat >= $startTime || $startFormat <= $endTime;
            $isEndValid   = $endFormat   >= $startTime || $endFormat   <= $endTime;
            if (!$isStartValid || !$isEndValid) {
                ReservationError::HourNotAllowed->throw($allowedHours['start'], $allowedHours['end']);
            }
        }
    }
}
