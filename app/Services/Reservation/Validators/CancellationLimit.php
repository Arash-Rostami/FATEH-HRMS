<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class CancellationLimit implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $limit = max(1, $context->policies['max_cancel_count'] ?? (int)floor($context->user->maximum / 4));

        $count = Reservation::where('user_id', $context->user->id)
            ->where('status', ReservationStatus::CancelledUser->value)
            ->where('cancelled_at', '>=', now()->subDays(30))
            ->toBase()->count();

        if ($count >= $limit) {
            throw new Exception("تعداد لغو رزروهای شما بیش از حد مجاز است؛ امکان ثبت رزرو جدید وجود ندارد.");
        }
    }
}
