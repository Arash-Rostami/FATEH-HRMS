<?php

namespace App\Services\Reservation\Validators;

use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Carbon\Carbon;
use Exception;

class AllowedHours implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->isFullDay) return;

        $allowedHours = $context->policies['allowed_hours'] ?? null;
        if (empty($allowedHours)) return;

        $from = Carbon::parse($context->start->toDateString() . ' ' . ($allowedHours['start'] ?? '00:00'));
        $to = Carbon::parse($context->start->toDateString() . ' ' . ($allowedHours['end'] ?? '23:59'));

        if ($context->start->lt($from) || $context->end->gt($to)) {
            throw new Exception("رزرو فقط بین ساعت {$allowedHours['start']} تا {$allowedHours['end']} مجاز است.");
        }
    }
}
