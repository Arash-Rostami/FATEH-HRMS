<?php

namespace App\Models\Concerns\Hooks;

use App\Enums\ReservationError;
use App\Services\Reservation\EventSyncService;

trait HasReservationHooks
{
    protected static function bootHasReservationHooks(): void
    {
        static::saving(self::guardSelfParentOnSaving(...));
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

    private static function syncEventOnSaved(self $reservation)
    {
        app(EventSyncService::class)->sync($reservation->loadMissing(['user', 'resource']));
    }

    private static function purgeEventOnDeleted(self $reservation)
    {
        app(EventSyncService::class)->purge($reservation);
    }
}
