<?php

namespace App\Livewire\Dashboard\Reservation\Actions;

use App\Livewire\Dashboard\Reservation\Validators\BookValidator;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookAction
{
    public function __construct(private BookValidator $validator) {}

    public function execute(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay): Reservation
    {
        $this->validator->validate($user, $resource, $start, $end, $isFullDay);

        return DB::transaction(function () use ($user, $resource, $start, $end, $isFullDay) {
            $reservation = Reservation::create([
                'user_id'     => $user->id,
                'resource_id' => $resource->id,
                'start_time'  => $start,
                'end_time'    => $end,
                'is_full_day' => $isFullDay,
                'status'      => 'active',
            ]);

            if ($resource->type === 'meeting' && $related = $resource->relatedUser) {
                $base = ['date' => $start, 'private' => true, 'description' => 'جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون'];
                foreach ([[$user, $related], [$related, $user]] as [$host, $guest]) {
                    Event::create(array_merge($base, ['user_id' => $host->id, 'title' => 'جلسه با ' . $guest->name]));
                }
            }

            return $reservation;
        });
    }
}
