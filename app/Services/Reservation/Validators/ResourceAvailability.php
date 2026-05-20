<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;
use Exception;

class ResourceAvailability implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $stats = Reservation::where('resource_id', $context->resource->id)
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::Released->value])
            ->when($context->excludeId, fn($q) => $q->where('id', '!=', $context->excludeId))
            ->when(
                $context->isFullDay,
                fn($q) => $q->whereDate('start_time', $context->start->toDateString()),
                fn($q) => $q->where('start_time', '<', $context->end)->where('end_time', '>', $context->start)
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as released_count',
                [ReservationStatus::Active->value, ReservationStatus::Released->value]
            )
            ->toBase()->first();

        if (($stats->active_count ?? 0) > 0 && ($stats->released_count ?? 0) === 0) {
            throw new Exception("گزینه مورد نظر در بازه زمانی انتخاب شده در دسترس نیست.");
        }
    }
}
