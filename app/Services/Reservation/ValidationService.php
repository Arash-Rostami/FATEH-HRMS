<?php

namespace App\Services\Reservation;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Resource;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Validators\ActiveLimit;
use App\Services\Reservation\Validators\AllowedDays;
use App\Services\Reservation\Validators\AllowedHours;
use App\Services\Reservation\Validators\BookingPermission;
use App\Services\Reservation\Validators\CancellationLimit;
use App\Services\Reservation\Validators\Duration;
use App\Services\Reservation\Validators\FullDay;
use App\Services\Reservation\Validators\RangeDuration;
use App\Services\Reservation\Validators\Recurrence;
use App\Services\Reservation\Validators\ResourceActive;
use App\Services\Reservation\Validators\ResourceAvailability;
use App\Services\Reservation\Validators\ResourceSchedule;
use App\Services\Reservation\Validators\TimeWindow;
use App\Services\Reservation\Validators\TypeActive;
use App\Services\Reservation\Validators\UserActive;
use App\Services\Reservation\Validators\UserConflict;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ValidationService
{
    private array $bookingRules = [
        UserActive::class => ['skip_admin' => true],
        TypeActive::class => ['skip_admin' => false],
        ResourceActive::class => ['skip_admin' => false],
        BookingPermission::class => ['skip_admin' => true],
        TimeWindow::class => ['skip_admin' => true],
        AllowedDays::class => ['skip_admin' => true, 'bend' => true],
        FullDay::class => ['skip_admin' => true],
        Duration::class => ['skip_admin' => true, 'bend' => true],
        RangeDuration::class => ['skip_admin' => false],
        AllowedHours::class => ['skip_admin' => true, 'bend' => true],
        ResourceSchedule::class => ['skip_admin' => true, 'bend' => true],
        Recurrence::class => ['skip_admin' => true],
        CancellationLimit::class => ['skip_admin' => true],
        ActiveLimit::class => ['skip_admin' => true],
        ResourceAvailability::class => ['skip_admin' => false],
        UserConflict::class => ['skip_admin' => true],
    ];

    public function flushPolicyCache(): void
    {
        ModelCacheVersion::bump(ReservationPolicy::class);
    }

    public function isTypeActive(string $resourceType): bool
    {
        return TypeActive::isTypeActive($this->getPolicies($resourceType));
    }

    public function disabledTypes(): array
    {
        return Cache::remember(ModelCacheVersion::key(ReservationPolicy::class, 'reservation_disabled_types'), 3600,
            fn() => collect(ResourceType::cases())
                ->reject(fn(ResourceType $type) => $this->isTypeActive($type->value))
                ->map(fn(ResourceType $type) => $type->value)
                ->values()
                ->all()
        );
    }

    public const CANCEL_WINDOW_DAYS = 30;

    public static function cancelWindowDays(array $policies): int
    {
        $days = $policies['cancel_window_days'] ?? null;

        return filled($days) ? max(1, min(365, (int) $days)) : self::CANCEL_WINDOW_DAYS;
    }

    public function getPolicies(string $resourceType): array
    {
        return Cache::remember(ModelCacheVersion::key(ReservationPolicy::class, "reservation_policies_{$resourceType}"), 3600,
            fn() => ReservationPolicy::where('resource_type', $resourceType)
                ->pluck('value', 'key')
                ->filter(fn($v) => $v !== null)
                ->toArray()
        );
    }

    public function activeLimitCount(int $userId, string $resourceType, Carbon $date): int
    {
        return Reservation::forUser($userId)
            ->whereHas('resource', fn($q) => $q->where('type', $resourceType))
            ->whereBetween('start_time', jalaliMonthBounds($date))
            ->held()
            ->toBase()->count();
    }

    public function cancelLimitCount(int $userId, string $resourceType): int
    {
        return $this->cancelLimitStats($userId, $resourceType)['count'];
    }

    public function cancelLimitStats(int $userId, string $resourceType, ?array $policies = null): array
    {
        $policies ??= $this->getPolicies($resourceType);

        $row = Reservation::forUser($userId)
            ->whereHas('resource', fn($q) => $q->where('type', $resourceType))
            ->where('status', ReservationStatus::CancelledUser->value)
            ->where(fn($q) => $q->whereNull('cancelled_at')->orWhere('cancelled_at', '>', now()->subDays(self::cancelWindowDays($policies))))
            ->toBase()
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(parent_id, id), '|', COALESCE(CAST(cancelled_at AS CHAR), id))) AS total, MIN(cancelled_at) AS oldest")
            ->first();

        return ['count' => (int) $row->total, 'oldest' => $row->oldest ? Carbon::parse($row->oldest) : null];
    }

    public function validateBooking(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay, ?array $recurrence = null, ?int $excludeId = null, ?User $actor = null): void
    {
        $this->runPipeline($user, $resource, $start, $end, $isFullDay, $recurrence, $excludeId, true, $actor);
    }

    public function validateCancellation(Reservation $reservation, User $user): void
    {
        if ($reservation->status !== ReservationStatus::Active->value) {
            ReservationError::NotActive->throw();
        }

        if (!$user->hasElevatedRole() && $reservation->user_id !== $user->id) {
            ReservationError::CancelForbidden->throw();
        }

        if (!$user->hasElevatedRole() && ($reservation->is_full_day ? $reservation->end_time : $reservation->start_time)?->isPast()) {
            ReservationError::NotActive->throw();
        }

        if (!$user->hasElevatedRole()) {
            $policies = $this->getPolicies($reservation->resource?->type ?? '');
            $limit = $policies['max_cancel_count'] ?? null;

            if ($limit !== null) {
                $count = $this->cancelLimitStats($user->id, $reservation->resource?->type ?? '', $policies)['count'];

                if ($count >= (int) $limit) {
                    ReservationError::CancelLimitReached->throw((int) $limit === 0 ? 0 : convertToPersian((string) self::cancelWindowDays($policies)));
                }
            }
        }
    }

    public function validateEdit(Reservation $reservation, User $user, Carbon $start, Carbon $end, bool $isFullDay, ?Resource $resource = null): void
    {
        $this->runPipeline($user, $resource ?? $reservation->resource, $start, $end, $isFullDay, null, $reservation->id, false);
    }

    private function runPipeline(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay, ?array $recurrence, ?int $excludeId, bool $enforceAllRules, ?User $actor = null): void
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
            if (($actor ?? $user)->hasElevatedRole() && $config['skip_admin'] && (!$enforceAllRules || ($config['bend'] ?? false))) {
                continue;
            }

            app($ruleClass)->validate($context);
        }
    }
}
