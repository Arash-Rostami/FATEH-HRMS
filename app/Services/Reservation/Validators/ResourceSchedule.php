<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Carbon\Carbon;

class ResourceSchedule implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->isRange()) return;

        $metadata = $context->resource->metadata;
        if (!is_array($metadata)) return;

        $availableDays = $metadata['available_days'] ?? null;
        if (is_array($availableDays) && $availableDays !== []) {
            $day = strtolower($context->start->englishDayOfWeek);
            if (!in_array($day, $availableDays)) {
                ReservationError::ResourceDayNotAllowed->throw($context->start->dayName);
            }
        }

        if ($context->isFullDay) return;

        $timeSlot = $metadata['time_slots'] ?? null;
        if (!is_array($timeSlot) || empty($timeSlot['start']) || empty($timeSlot['end'])) return;

        $slotStart = Carbon::parse($timeSlot['start']);
        $slotEnd = Carbon::parse($timeSlot['end']);

        $windowStart = $context->start->copy()->setTime($slotStart->hour, $slotStart->minute, $slotStart->second);
        $windowEnd = $context->start->copy()->setTime($slotEnd->hour, $slotEnd->minute, $slotEnd->second);

        if ($context->start->lessThan($windowStart) || $context->end->greaterThan($windowEnd)) {
            ReservationError::ResourceHourNotAllowed->throw($timeSlot['start'], $timeSlot['end']);
        }
    }
}
