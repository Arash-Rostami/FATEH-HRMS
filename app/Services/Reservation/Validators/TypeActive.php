<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class TypeActive implements BookingRule
{
    public static function isTypeActive(array $policies): bool
    {
        $windowDays = (int)($policies['window_days'] ?? 1);
        $windowHours = (int)($policies['window_hours'] ?? 1);

        return $windowDays !== 0 || $windowHours !== 0;
    }

    public function validate(BookingContext $context): void
    {
        if (!self::isTypeActive($context->policies)) {
            ReservationError::TypeInactive->throw();
        }
    }
}
