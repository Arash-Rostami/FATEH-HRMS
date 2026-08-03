<?php

namespace App\Services\Reservation;

use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Reservation;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EventSyncService
{
    private const DESCRIPTION_PREFIX = 'جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون #';

    public static function isReservationEvent(?string $description): bool
    {
        return $description !== null && str_starts_with($description, self::DESCRIPTION_PREFIX);
    }

    public static function reservationIdFrom(?string $description): ?int
    {
        if (!self::isReservationEvent($description)) {
            return null;
        }

        return (int)substr($description, strlen(self::DESCRIPTION_PREFIX));
    }

    public function purge(Reservation $reservation): void
    {
        $description = $this->description($reservation);

        Event::query()
            ->where('user_id', $reservation->user_id)
            ->where('description', $description)
            ->first()
            ?->delete();

        $this->pruneOtherOwners($description, $reservation->user_id);

        DB::afterCommit(function (): void {
            Cache::forget('countdown:active');
            StateService::flush();
        });
    }

    public function sync(Reservation $reservation): void
    {
        $reservation->loadMissing(['user', 'resource.relatedUser']);

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

        $event = Event::updateOrCreate(
            [
                'user_id' => $booker->id,
                'description' => $description,
            ],
            [
                'title' => "جلسه {$booker->name} و {$related->name}",
                'date' => $reservation->start_time,
                'private' => true,
            ]
        );

        $this->pruneOtherOwners($description, $booker->id);

        $event->shares()
            ->where('user_id', '!=', $related->id)
            ->get()
            ->each(fn(EventShare $share) => $share->delete());

        EventShare::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $related->id],
            ['shared_by' => $booker->id]
        );
    }

    private function pruneOtherOwners(string $description, int $keepUserId): void
    {
        Event::query()
            ->where('description', $description)
            ->where('user_id', '!=', $keepUserId)
            ->get()
            ->each(fn(Event $event) => $event->delete());
    }

    private function description(Reservation $reservation): string
    {
        return self::DESCRIPTION_PREFIX . $reservation->id;
    }
}
