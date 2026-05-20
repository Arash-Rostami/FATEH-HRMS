<?php

namespace App\Filament\Resources\ReservationResource\Actions;

use App\Models\Reservation;
use Carbon\Carbon;

class GenerateSeriesAction
{
    public function execute(Reservation $master, string $pattern, int $count): void
    {
        $intervalDays = $pattern === 'weekly' ? 7 : 1;
        $start = Carbon::parse($master->start_time);
        $end = Carbon::parse($master->end_time);
        $count = max(2, min(52, $count));

        for ($i = 1; $i < $count; $i++) {
            Reservation::create([
                'user_id'     => $master->user_id,
                'resource_id' => $master->resource_id,
                'start_time'  => $start->copy()->addDays($i * $intervalDays),
                'end_time'    => $end->copy()->addDays($i * $intervalDays),
                'is_full_day' => $master->is_full_day,
                'status'      => 'active',
                'parent_id'   => $master->id,
            ]);
        }
    }
}
