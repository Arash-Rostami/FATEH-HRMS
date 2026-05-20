<?php

namespace App\Services\Reservation\Validators;

use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class AllowedDays implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $allowedDays = $context->policies['allowed_days'] ?? null;
        if (empty($allowedDays)) return;

        $day = strtolower($context->start->englishDayOfWeek);
        if (!in_array($day, $allowedDays)) {
            throw new Exception("رزرو در روز {$context->start->dayName} برای این نوع منبع مجاز نیست.");
        }
    }
}
