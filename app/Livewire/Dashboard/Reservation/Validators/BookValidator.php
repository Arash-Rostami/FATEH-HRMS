<?php

namespace App\Livewire\Dashboard\Reservation\Validators;

use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

class BookValidator
{
    private const STATUS_ACTIVE = 'active';
    private const STATUS_RELEASED = 'released';
    private const STATUS_CANCELLED_USER = 'cancelled_user';

    public function validate(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay): void
    {
        if (!$user->isActive()) {
            throw new Exception("حساب کاربری شما در حال حاضر فعال نیست.");
        }

        if (!$this->canUserBook($user, $resource->type)) {
            throw new Exception("شما اجازه رزرو این نوع گزینه را ندارید.");
        }

        if (!$this->checkCancellationLimit($user)) {
            throw new Exception("تعداد لغو رزروهای شما بیش از حد مجاز است؛ امکان ثبت رزرو جدید وجود ندارد.");
        }

        if (!$this->checkMonthlyLimit($user, $resource->type, $start)) {
            throw new Exception("سقف رزرو ماهانه شما برای این مورد به پایان رسیده است.");
        }

        $policies = $this->getPolicies($resource->type);
        $this->validateTimeWindow($start, $policies);

        if (!$this->isResourceAvailable($resource, $start, $end, $isFullDay)) {
            throw new Exception("گزینه مورد نظر در بازه زمانی انتخاب شده در دسترس نیست.");
        }

        if ($this->hasUserConflict($user, $resource, $start, $end, $isFullDay)) {
            throw new Exception("شما در این بازه زمانی، رزرو فعال دیگری از همین نوع دارید.");
        }
    }

    private function canUserBook(User $user, string $type): bool
    {
        $permissions = is_array($user->booking) ? $user->booking : json_decode($user->booking, true);
        return ($permissions['all'] ?? false) || ($permissions[$type] ?? false);
    }

    private function checkCancellationLimit(User $user): bool
    {
        $limit = max(1, floor($user->maximum / 4));

        $cancelledCount = Reservation::where('user_id', $user->id)
            ->where('status', self::STATUS_CANCELLED_USER)
            ->where('cancelled_at', '>=', now()->subDays(30))
            ->toBase()
            ->count();

        return $cancelledCount < $limit;
    }


    private function checkMonthlyLimit(User $user, string $type, Carbon $date): bool
    {
        if ($type !== 'spot') {
            return true;
        }

        $count = Reservation::where('user_id', $user->id)
            ->whereHas('resource', fn($q) => $q->where('type', 'spot'))
            ->whereMonth('start_time', $date->month)
            ->whereYear('start_time', $date->year)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_RELEASED])
            ->toBase()
            ->count();

        return $count < $user->maximum;
    }

    private function getPolicies(string $resourceType): array
    {
        return Cache::remember("reservation_policies_{$resourceType}", 3600,
            fn() => ReservationPolicy::where('resource_type', $resourceType)
                ->pluck('value', 'key')
                ->toArray()
        );
    }

    private function hasUserConflict(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay): bool
    {
        return Reservation::where('user_id', $user->id)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_RELEASED])
            ->whereHas('resource', fn($q) => $q->where('type', $resource->type))
            ->when(
                $isFullDay,
                fn($q) => $q->whereDate('start_time', $start->toDateString()),
                fn($q) => $q->where('start_time', '<', $end)->where('end_time', '>', $start)
            )
            ->exists();
    }

    private function isResourceAvailable(Resource $resource, Carbon $start, Carbon $end, bool $isFullDay): bool
    {
        $query = Reservation::where('resource_id', $resource->id)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_RELEASED])
            ->when(
                $isFullDay,
                fn($q) => $q->whereDate('start_time', $start->toDateString()),
                fn($q) => $q->where('start_time', '<', $end)->where('end_time', '>', $start)
            );

        $stats = $query->selectRaw('
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as released_count
        ', [self::STATUS_ACTIVE, self::STATUS_RELEASED])
            ->toBase()
            ->first();

        $hasActive = ($stats->active_count ?? 0) > 0;
        $hasReleased = ($stats->released_count ?? 0) > 0;

        return !($hasActive && !$hasReleased);
    }

    private function validateTimeWindow(Carbon $start, array $policies): void
    {
        $windowDays = $policies['window_days'] ?? 21;
        $windowHours = $policies['window_hours'] ?? 0;

        if ($start->isAfter(now()->addDays($windowDays))) {
            throw new Exception("تاریخ انتخابی خارج از بازه زمانی مجاز (حداکثر {$windowDays} روز آینده) است.");
        }

        if ($start->isSameDay(now()) && now()->diffInHours($start, false) < $windowHours) {
            throw new Exception("رزرو باید حداقل {$windowHours} ساعت قبل از زمان شروع ثبت شود.");
        }
    }
}
