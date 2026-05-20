<?php

namespace App\Services\Reservation\Validators;

use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class BookingPermission implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $permissions = is_array($context->user->booking)
            ? $context->user->booking
            : json_decode($context->user->booking, true);

        if (!($permissions['all'] ?? false) && !($permissions[$context->resource->type] ?? false)) {
            throw new Exception("شما اجازه رزرو این نوع گزینه را ندارید.");
        }
    }
}
