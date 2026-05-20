<?php

namespace App\Services\Reservation\Validators;

use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class Duration implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        if ($context->isFullDay) return;

        $minutes = $context->start->diffInMinutes($context->end);
        $min = $context->policies['min_duration_minutes'] ?? null;
        $max = $context->policies['max_duration_minutes'] ?? null;

        if ($min && $minutes < $min) throw new Exception("حداقل مدت رزرو {$min} دقیقه است.");
        if ($max && $minutes > $max) throw new Exception("حداکثر مدت رزرو {$max} دقیقه است.");
    }
}
