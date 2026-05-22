<?php

namespace App\Services\Reservation\Validators;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Contracts\BookingRule;

class ResourceAvailability implements BookingRule
{
    public function validate(BookingContext $context): void
    {
        $stats = Reservation::where('resource_id', $context->resource->id)
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::Released->value])
            ->when($context->excludeId, fn($q) => $q->where('id', '!=', $context->excludeId))
            ->where(fn($query) => $query
                ->where(fn($q) => $q->where('start_time', '<', $context->end)->where('end_time', '>', $context->start))
                ->orWhere(fn($q) => $q->whereNull('start_time')
                    ->where('created_at', '>=', $context->start->copy()->startOfDay())
                    ->where('created_at', '<=', $context->end->copy()->endOfDay()))
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as released_count',
                [ReservationStatus::Active->value, ReservationStatus::Released->value]
            )
            ->toBase()->first();

        $allowOverlap = $context->policies['allow_overlap_release'] ?? false;
        $activeCount = (int)($stats->active_count ?? 0);
        $releasedCount = (int)($stats->released_count ?? 0);

        $taken = $allowOverlap
            ? ($activeCount > 0)
            : ($activeCount > 0 || $releasedCount > 0);

        if ($taken) {
            ReservationError::ResourceTaken->throw();
        }
    }
}
