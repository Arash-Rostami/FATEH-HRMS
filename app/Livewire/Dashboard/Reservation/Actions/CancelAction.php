<?php

namespace App\Livewire\Dashboard\Reservation\Actions;

use App\Models\Reservation;
use App\Models\User;
use Exception;

class CancelAction
{
    public function execute(Reservation $reservation, User $user): void
    {
        if ($reservation->status !== 'active')
            throw new Exception("این رزرو در حال حاضر فعال نیست.");

        $isAdmin = $user->isAdmin();

        if (!$isAdmin && $reservation->user_id !== $user->id)
            throw new Exception("شما اجازه لغو این رزرو را ندارید.");

        $reservation->update([
            'status'          => $isAdmin ? 'cancelled_admin' : 'cancelled_user',
            'cancelled_by_id' => $user->id,
            'cancelled_at'    => now(),
            'cancel_reason'   => null,
        ]);
    }
}
