<?php

namespace App\Livewire\Dashboard\Reservation\Actions;

use App\Models\Event;
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

            if ($resource->type === 'meeting' && $related = $resource->relatedUser) {
                $base = ['date' => $start, 'private' => true, 'description' => 'جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون'];
                foreach ([[$user, $related], [$related, $user]] as [$host, $guest]) {
                    Event::create(array_merge($base, ['user_id' => $host->id, 'title' => 'جلسه با ' . $guest->name]));
                }
            }

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

        for ($i = 1; $i < $count; $i++) {
            Reservation::create([
                'user_id' => $master->user_id,
                'resource_id' => $master->resource_id,
                'start_time' => $start->copy()->addDays($i * $intervalDays),
                'end_time' => $end->copy()->addDays($i * $intervalDays),
                'is_full_day' => $isFullDay,
                'status' => 'active',
                'parent_id' => $master->id,
            ]);
        }
    }
}
