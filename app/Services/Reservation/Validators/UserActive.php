<?php

namespace App\Services\Reservation\Validators;

use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class UserActive implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if (!$context->user->isActive()) {
            throw new Exception("حساب کاربری شما در حال حاضر فعال نیست.");
        }
    }
}
