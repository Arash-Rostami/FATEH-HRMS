<?php

namespace App\Livewire\Dashboard\Reservation\Actions;

use App\Models\Reservation;
use App\Models\User;
use App\Services\Reservation\ValidationService;

class CancelAction
{
    public function __construct(private ValidationService $validator) { }

    public function execute(Reservation $reservation, User $user, ?string $cancelReason = null): void
    {
        $this->validator->validateCancellation($reservation, $user);

        $reservation->update([
            'status' => $user->isAdmin() ? 'cancelled_admin' : 'cancelled_user',
            'cancelled_by_id' => $user->id,
            'cancelled_at' => now(),
            'cancel_reason' => $cancelReason,
        ]);
    }
}
