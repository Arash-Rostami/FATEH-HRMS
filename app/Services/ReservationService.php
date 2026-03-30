<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Resource;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReservationService
{

    public function canUserBook(User $user, string $type): bool
    {
        $permissions = is_array($user->booking) ? $user->booking : json_decode($user->booking, true);

        return ($permissions['all'] ?? false) || ($permissions[$type] ?? false);
    }

    public function cancelReservation(Reservation $reservation, User $user, string $reason = null): void
    {
        if ($reservation->status !== 'active') throw new Exception("این رزرو در حال حاضر فعال نیست.");

        $isAdmin = $user->isAdmin();

        if (!$isAdmin && $reservation->user_id !== $user->id) throw new Exception("شما اجازه لغو این رزرو را ندارید.");

        $reservation->update([
            'status' => $isAdmin ? 'cancelled_admin' : 'cancelled_user',
            'cancelled_by_id' => $user->id,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
    }

    public function checkCancellationLimit(User $user, Carbon $date): bool
    {
        return Reservation::where('user_id', $user->id)
                ->where('status', 'cancelled_user')
                ->where('cancelled_at', '>=', $date->copy()->subDays(30))
                ->count() < max(1, floor($user->maximum / 4));
    }

    public function checkMonthlyLimit(User $user, string $type, Carbon $date): bool
    {
        return $type !== 'spot' || Reservation::where('user_id', $user->id)
                ->whereHas('resource', fn($q) => $q->where('type', 'spot'))
                ->whereMonth('start_time', $date->month)
                ->whereYear('start_time', $date->year)
                ->whereIn('status', ['active', 'released'])
                ->count() < $user->maximum;
    }

    public function createReservation(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay): Reservation
    {
        if (!$user->isActive()) throw new Exception("حساب کاربری شما در حال حاضر فعال نیست.");
        if (!$this->canUserBook($user, $resource->type)) throw new Exception("شما اجازه رزرو این نوع گزینه را ندارید.");
        if (!$this->checkCancellationLimit($user, now())) throw new Exception("تعداد لغو رزروهای شما بیش از حد مجاز است؛ امکان ثبت رزرو جدید وجود ندارد.");
        if (!$this->checkMonthlyLimit($user, $resource->type, $start)) throw new Exception("سقف رزرو ماهانه شما برای این مورد به پایان رسیده است.");

        $policies = $this->getResourcePolicies($resource->type);
        $windowDays = $policies['window_days'] ?? 21;
        $windowHours = $policies['window_hours'] ?? 0;

        if ($start->isAfter(now()->addDays($windowDays))) throw new Exception("تاریخ انتخابی خارج از بازه زمانی مجاز (حداکثر {$windowDays} روز آینده) است.");
        if ($start->isSameDay(now()) && now()->diffInHours($start, false) < $windowHours) throw new Exception("رزرو باید حداقل {$windowHours} ساعت قبل از زمان شروع ثبت شود.");
        if (!$this->isResourceAvailable($resource, $start, $end, $isFullDay)) throw new Exception("گزینه مورد نظر در بازه زمانی انتخاب شده در دسترس نیست.");

        $hasConflict = Reservation::where('user_id', $user->id)
            ->whereHas('resource', fn($q) => $q->where('type', $resource->type))
            ->where('status', 'active')
            ->when($isFullDay,
                fn($q) => $q->whereDate('start_time', $start->toDateString()),
                fn($q) => $q->where('start_time', '<', $end)->where('end_time', '>', $start)
            )->exists();

        if ($hasConflict) throw new Exception("شما در این بازه زمانی، رزرو فعال دیگری از همین نوع دارید.");

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

    public function getResourcePolicies(string $type): array
    {
        return Cache::remember("reservation_policies_{$type}", 3600, fn() => ReservationPolicy::where('resource_type', $type)
            ->pluck('value', 'key')
            ->toArray());
    }

    public function isResourceAvailable(Resource $resource, Carbon $start, Carbon $end, bool $isFullDay): bool
    {
        $statuses = Reservation::where('resource_id', $resource->id)
            ->whereIn('status', ['active', 'released'])
            ->when($isFullDay,
                fn($q) => $q->whereDate('start_time', $start->toDateString()),
                fn($q) => $q->where('start_time', '<', $end)->where('end_time', '>', $start)
            )->pluck('status');

        if ($statuses->isEmpty()) return true;

        return !($statuses->contains('active') && !$statuses->contains('released'));
    }
}
