<?php

namespace App\Livewire\Dashboard\Reservation\Actions;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Reservation\ValidationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CancelAction
{
    public function __construct(private ValidationService $validator) { }

    public function execute(Reservation $reservation, User $user, ?string $cancelReason = null): void
    {
        $this->validator->validateCancellation($reservation, $user);

        $policies = $this->validator->getPolicies($reservation->resource?->type ?? '');
        $allowPartial = $policies['allow_partial_cancel'] ?? true;

        $targets = $allowPartial
            ? collect([$reservation])
            : $this->seriesReservations($reservation);

        $attributes = [
            'status' => $user->hasElevatedRole()
                ? ReservationStatus::CancelledAdmin->value
                : ReservationStatus::CancelledUser->value,
            'cancelled_by_id' => $user->id,
            'cancelled_at' => now(),
            'cancel_reason' => $cancelReason,
        ];

        DB::transaction(fn() => $targets->each->update($attributes));
    }

    private function seriesReservations(Reservation $reservation): Collection
    {
        if ($reservation->parent_id === $reservation->id) {
            Log::critical("Reservation data corruption detected for ID: {$reservation->id}");
            ReservationError::DataCorruption->throw();
        }

        $root = $reservation->parent_id
            ? Reservation::with('occurrences')->find($reservation->parent_id)
            : $reservation->load('occurrences');

        if (!$root) {
            return collect([$reservation]);
        }

        return $root->occurrences
            ->where('status', ReservationStatus::Active->value)
            ->push($root)
            ->filter(fn(Reservation $r) => $r->status === ReservationStatus::Active->value);
    }
}
