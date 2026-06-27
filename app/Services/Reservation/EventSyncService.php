<?php

namespace App\Services\Reservation;

use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Models\Event;
use App\Models\Reservation;

class EventSyncService
{
    public function purge(Reservation $reservation): void
    {
        $userIds = array_filter([
            $reservation->user_id,
            $reservation->resource?->relatedUser?->id,
        ]);

        Event::query()
            ->where('description', $this->description($reservation))
            ->when($reservation->start_time, fn($query) => $query->whereDate('date', $reservation->start_time))
            ->when($userIds, fn($query) => $query->whereIn('user_id', $userIds))
            ->delete();
    }

    public function sync(Reservation $reservation): void
    {
        $booker = $reservation->user;
        $related = $reservation->resource?->relatedUser;

        if (
            $reservation->resource?->type !== ResourceType::Meeting->value ||
            $reservation->status !== ReservationStatus::Active->value ||
            !$reservation->start_time ||
            !$booker ||
            !$related
        ) {
            $this->purge($reservation);

            return;
        }

        $description = $this->description($reservation);

        foreach ([
                     [$booker->id, "جلسه با {$related->name}"],
                     [$related->id, "جلسه با {$booker->name}"],
                 ] as [$userId, $title]) {
            Event::updateOrCreate(
                [
                    'user_id' => $userId,
                    'description' => $description,
                ],
                [
                    'title' => $title,
                    'date' => $reservation->start_time,
                    'private' => true,
                ]
            );
        }
    }

    private function description(Reservation $reservation): string
    {
        return "جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون #{$reservation->id}";
    }
}
