<?php

namespace App\Livewire\Dashboard\Reservation\Actions;

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Services\Reservation\ValidationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookAction
{
    public function __construct(private ValidationService $validator) { }

    public function execute(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay, ?array $recurrence = null): Reservation
    {
        $this->validator->validateBooking($user, $resource, $start, $end, $isFullDay, $recurrence);

        return DB::transaction(function () use ($user, $resource, $start, $end, $isFullDay, $recurrence) {
            $master = Reservation::create([
                'user_id' => $user->id,
                'resource_id' => $resource->id,
                'start_time' => $start,
                'end_time' => $end,
                'is_full_day' => $isFullDay,
                'status' => 'active',
            ]);

            if ($recurrence) {
                $this->generateOccurrences($master, $start, $end, $isFullDay, $recurrence);
            }

            return $master;
        });
    }

    private function generateOccurrences(Reservation $master, Carbon $start, Carbon $end, bool $isFullDay, array $recurrence): void
    {
        $intervalDays = ($recurrence['pattern'] ?? 'daily') === 'weekly' ? 7 : 1;
        $count = max(2, min(52, (int)($recurrence['count'] ?? 4)));
        $resource = $master->resource;
        $booker = $master->user;

        for ($i = 1; $i < $count; $i++) {
            $occStart = $start->copy()->addDays($i * $intervalDays);
            $occEnd = $end->copy()->addDays($i * $intervalDays);

            try {
                $this->validator->validateBooking($booker, $resource, $occStart, $occEnd, $isFullDay);
            } catch (\Exception) {
                continue;
            }

            Reservation::create([
                'user_id' => $master->user_id,
                'resource_id' => $master->resource_id,
                'start_time' => $occStart,
                'end_time' => $occEnd,
                'is_full_day' => $isFullDay,
                'status' => 'active',
                'parent_id' => $master->id,
            ]);
        }
    }
}
