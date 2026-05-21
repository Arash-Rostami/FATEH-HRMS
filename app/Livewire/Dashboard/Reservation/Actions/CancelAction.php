<?php

namespace App\Livewire\Dashboard\Reservation\Actions;

use App\Enums\ReservationStatus;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Reservation\ValidationService;
use Illuminate\Support\Collection;


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
            'status' => $user->isAdmin()
                ? ReservationStatus::CancelledAdmin->value
                : ReservationStatus::CancelledUser->value,
            'cancelled_by_id' => $user->id,
            'cancelled_at' => now(),
            'cancel_reason' => $cancelReason,
        ];

        $targets->each(function (Reservation $r) use ($attributes) {
            $r->update($attributes);

            if ($r->resource?->type === 'meeting') {
                Event::whereDate('date', $r->start_time->toDateString())
                    ->where('description', 'like', '%سیستم رزرواسیون%')
                    ->whereIn('user_id', array_filter([$r->user_id, $r->resource->relatedUser?->id]))
                    ->delete();
            }
        });
    }

    private function seriesReservations(Reservation $reservation): Collection
    {
        if ($reservation->parent_id === $reservation->id) {
            return collect([$reservation]);
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
