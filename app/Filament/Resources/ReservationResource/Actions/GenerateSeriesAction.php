<?php

namespace App\Filament\Resources\ReservationResource\Actions;

use App\Models\Reservation;
use App\Services\Reservation\ValidationService;
use Carbon\Carbon;
use Exception;

class GenerateSeriesAction
{
    public function __construct(private ValidationService $validator) { }

    public function execute(Reservation $master, string $pattern, int $count): void
    {
        $intervalDays = $pattern === 'weekly' ? 7 : 1;
        $start = Carbon::parse($master->start_time);
        $end = Carbon::parse($master->end_time);
        $count = max(2, min(52, $count));

        $resource = $master->resource;
        $user = $master->user;

        for ($i = 1; $i < $count; $i++) {
            $occStart = $start->copy()->addDays($i * $intervalDays);
            $occEnd = $end->copy()->addDays($i * $intervalDays);

            try {
                $this->validator->validateBooking($user, $resource, $occStart, $occEnd, (bool)$master->is_full_day);
            } catch (Exception) {
                continue;
            }

            Reservation::create([
                'user_id' => $master->user_id,
                'resource_id' => $master->resource_id,
                'start_time' => $occStart,
                'end_time' => $occEnd,
                'is_full_day' => $master->is_full_day,
                'status' => 'active',
                'parent_id' => $master->id,
            ]);
        }
    }
}
