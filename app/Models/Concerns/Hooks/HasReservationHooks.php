<?php

namespace App\Models\Concerns\Hooks;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Services\Reservation\EventSyncService;
use Illuminate\Support\Facades\Auth;

trait HasReservationHooks
{
    protected static function bootHasReservationHooks(): void
    {
        static::saving(self::guardSelfParentOnSaving(...));
        static::saving(self::syncCancellationOnSaving(...));
        static::saved(self::syncEventOnSaved(...));
        static::deleted(self::purgeEventOnDeleted(...));
    }

    private static function guardSelfParentOnSaving(self $reservation)
    {
        if ($reservation->parent_id && $reservation->id && (int)$reservation->parent_id === (int)$reservation->id) {
            $reservation->parent_id = null;
            ReservationError::DataCorruption->throw();
        }
    }

    private static function syncCancellationOnSaving(self $reservation)
    {
        if (!$reservation->exists || !$reservation->isDirty('status')) {
            return;
        }

        match ($reservation->status) {
            ReservationStatus::CancelledUser->value, ReservationStatus::CancelledAdmin->value => $reservation->fill([
                'cancelled_by_id' => $reservation->cancelled_by_id ?? Auth::id(),
                'cancelled_at' => $reservation->cancelled_at ?? now(),
            ]),
            ReservationStatus::Active->value => $reservation->fill(['cancelled_by_id' => null, 'cancelled_at' => null, 'cancel_reason' => null]),
            ReservationStatus::Released->value => $reservation->isRange() && $reservation->start_time?->isPast() && $reservation->end_time?->isFuture() && !$reservation->isDirty('end_time')
                ? $reservation->fill(['end_time' => now()])
                : null,
            default => null,
        };
    }

    private static function syncEventOnSaved(self $reservation)
    {
        app(EventSyncService::class)->sync($reservation->loadMissing(['user', 'resource']));
    }

    private static function purgeEventOnDeleted(self $reservation)
    {
        app(EventSyncService::class)->purge($reservation);
    }
}
