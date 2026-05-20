<?php

namespace App\Services\Reservation\Validators;

use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class FullDay implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->isFullDay && !($context->policies['allow_full_day'] ?? true)) {
            throw new Exception("رزرو تمام‌روز برای این نوع منبع مجاز نیست.");
        }
    }
}
