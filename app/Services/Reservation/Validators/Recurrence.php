<?php

namespace App\Services\Reservation\Validators;

use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class Recurrence implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->recurrence && !($context->policies['allow_repeat'] ?? true)) {
            throw new Exception("رزرو تکراری برای این نوع منبع مجاز نیست.");
        }
    }
}
