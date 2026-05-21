<?php

namespace App\Services\Reservation;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Resource;
use App\Models\User;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Validators\ActiveLimit;
use App\Services\Reservation\Validators\AllowedDays;
use App\Services\Reservation\Validators\AllowedHours;
use App\Services\Reservation\Validators\BookingPermission;
use App\Services\Reservation\Validators\CancellationLimit;
use App\Services\Reservation\Validators\Duration;
use App\Services\Reservation\Validators\FullDay;
use App\Services\Reservation\Validators\Recurrence;
use App\Services\Reservation\Validators\ResourceAvailability;
use App\Services\Reservation\Validators\TimeWindow;
use App\Services\Reservation\Validators\UserActive;
use App\Services\Reservation\Validators\UserConflict;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

class ValidationService
{
    private array $bookingRules = [
        UserActive::class,
        BookingPermission::class,
        TimeWindow::class,
        AllowedDays::class,
        FullDay::class,
        Duration::class,
        AllowedHours::class,
        Recurrence::class,
        CancellationLimit::class,
        ActiveLimit::class,
        ResourceAvailability::class,
        UserConflict::class,
    ];

    public function flushPolicyCache(string $resourceType): void
    {
        Cache::forget("reservation_policies_{$resourceType}");
    }

    public function getPolicies(string $resourceType): array
    {
        return Cache::remember("reservation_policies_{$resourceType}", 3600,
            fn() => ReservationPolicy::where('resource_type', $resourceType)
                ->pluck('value', 'key')
                ->filter()
                ->toArray()
        );
    }

    public function validateBooking(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay, ?array $recurrence = null): void
    {
        $context = new BookingContext(
            $user, $resource, $start, $end, $isFullDay,
            $this->getPolicies($resource->type),
            $recurrence,
        );

        foreach ($this->bookingRules as $rule) {
            app($rule)->validate($context);
        }
    }

    public function validateCancellation(Reservation $reservation, User $user): void
    {
        if ($reservation->status !== ReservationStatus::Active->value) {
            ReservationError::NotActive->throw();
        }

        if (!$user->isAdmin() && $reservation->user_id !== $user->id) {
            ReservationError::CancelForbidden->throw();
        }

        if (!$user->isAdmin()) {
            $policies = $this->getPolicies($reservation->resource?->type ?? '');
            $limit = $policies['max_cancel_count'] ?? null;

            if ($limit !== null) {
                $count = Reservation::where('user_id', $user->id)
                    ->where('status', ReservationStatus::CancelledUser->value)
                    ->where('cancelled_at', '>=', now()->subDays(30))
                    ->toBase()->count();

                if ($count >= $limit) {
                    ReservationError::CancelLimitReached->throw();
                }
            }
        }
    }

    public function validateEdit(Reservation $reservation, User $user, Carbon $start, Carbon $end, bool $isFullDay): void
    {
        $resource = $reservation->resource;

        if (!$user->isAdmin()) {
            $this->validateBooking($user, $resource, $start, $end, $isFullDay);
        }

        $context = new BookingContext(
            $user, $resource, $start, $end, $isFullDay,
            $this->getPolicies($resource->type),
            null,
            $reservation->id,
        );

        app(ResourceAvailability::class)->validate($context);
    }
}
