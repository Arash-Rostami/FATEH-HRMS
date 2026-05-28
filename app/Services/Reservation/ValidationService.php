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
use App\Services\Reservation\Validators\ResourceActive;
use App\Services\Reservation\Validators\ResourceAvailability;
use App\Services\Reservation\Validators\TimeWindow;
use App\Services\Reservation\Validators\UserActive;
use App\Services\Reservation\Validators\UserConflict;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ValidationService
{
    private array $bookingRules = [
        UserActive::class => ['skip_admin' => true],
        ResourceActive::class => ['skip_admin' => false],
        BookingPermission::class => ['skip_admin' => true],
        TimeWindow::class => ['skip_admin' => true],
        AllowedDays::class => ['skip_admin' => true],
        FullDay::class => ['skip_admin' => true],
        Duration::class => ['skip_admin' => true],
        AllowedHours::class => ['skip_admin' => true],
        Recurrence::class => ['skip_admin' => true],
        CancellationLimit::class => ['skip_admin' => true],
        ActiveLimit::class => ['skip_admin' => true],
        ResourceAvailability::class => ['skip_admin' => false],
        UserConflict::class => ['skip_admin' => true],
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
                ->filter(fn($v) => $v !== null)
                ->toArray()
        );
    }

    public function validateBooking(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay, ?array $recurrence = null, ?int $excludeId = null): void
    {
        $this->runPipeline($user, $resource, $start, $end, $isFullDay, $recurrence, $excludeId, true);
    }

    public function validateCancellation(Reservation $reservation, User $user): void
    {
        if ($reservation->status !== ReservationStatus::Active->value) {
            ReservationError::NotActive->throw();
        }

        if (!$user->hasElevatedRole() && $reservation->user_id !== $user->id) {
            ReservationError::CancelForbidden->throw();
        }

        if (!$user->hasElevatedRole()) {
            $policies = $this->getPolicies($reservation->resource?->type ?? '');
            $limit = $policies['max_cancel_count'] ?? null;

            if ($limit !== null) {
                $resourceType = $reservation->resource?->type ?? '';

                $count = Reservation::where('user_id', $user->id)
                    ->where('status', ReservationStatus::CancelledUser->value)
                    ->where(fn($q) => $q->whereNull('cancelled_at')->orWhere('cancelled_at', '>=', now()->subDays(30)))
                    ->whereHas('resource', fn($q) => $q->where('type', $resourceType))
                    ->toBase()->count();

                if ($count >= $limit) {
                    ReservationError::CancelLimitReached->throw();
                }
            }
        }
    }

    public function validateEdit(Reservation $reservation, User $user, Carbon $start, Carbon $end, bool $isFullDay): void
    {
        $this->runPipeline($user, $reservation->resource, $start, $end, $isFullDay, null, $reservation->id, false);
    }

    private function runPipeline(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay, ?array $recurrence, ?int $excludeId, bool $enforceAllRules): void
    {
        if ($start->gte($end)) {
            ReservationError::InvalidTimeRange->throw();
        }

        $context = new BookingContext(
            $user,
            $resource,
            $start,
            $end,
            $isFullDay,
            $this->getPolicies($resource->type),
            $recurrence,
            $excludeId
        );

        foreach ($this->bookingRules as $ruleClass => $config) {
            if (!$enforceAllRules && $user->hasElevatedRole() && $config['skip_admin']) {
                continue;
            }

            app($ruleClass)->validate($context);
        }
    }
}
