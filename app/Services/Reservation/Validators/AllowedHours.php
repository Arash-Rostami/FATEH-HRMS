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

        $policyStart = Carbon::parse($allowedHours['start'] ?? '00:00:00');
        $policyEnd = Carbon::parse($allowedHours['end'] ?? '23:59:59');

        $windowStart = $context->start->copy()->setTime($policyStart->hour, $policyStart->minute, $policyStart->second);
        $windowEnd = $context->start->copy()->setTime($policyEnd->hour, $policyEnd->minute, $policyEnd->second);

        if ($policyStart->greaterThan($policyEnd)) {
            if ($context->start->format('H:i:s') <= $policyEnd->format('H:i:s')) {
                $windowStart->subDay();
            } else {
                $windowEnd->addDay();
            }
        }

        if ($context->start->lessThan($windowStart) || $context->end->greaterThan($windowEnd)) {
            ReservationError::HourNotAllowed->throw($allowedHours['start'] ?? '00:00:00', $allowedHours['end'] ?? '23:59:59');
        }
    }
}
