<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function getResourcePolicies(string $type): array
    {
        return ReservationPolicy::where('resource_type', $type)
            ->pluck('value', 'key')
            ->toArray();
    }

    public function canUserBook(User $user, string $type): bool
    {
        $permissions = is_array($user->booking) ? $user->booking : json_decode($user->booking, true);
        if (($permissions['all'] ?? false) === true) {
            return true;
        }
        return ($permissions[$type] ?? false) === true;
    }

    public function checkMonthlyLimit(User $user, string $type, Carbon $date): bool
    {
        if ($type !== 'spot') {
            return true; // only parking spots have a limit based on maximum
        }

        $count = Reservation::where('user_id', $user->id)
            ->whereHas('resource', fn ($q) => $q->where('type', 'spot'))
            ->whereMonth('start_time', $date->month)
            ->whereYear('start_time', $date->year)
            ->whereIn('status', ['active', 'released'])
            ->count();

        return $count < $user->maximum;
    }

    public function checkCancellationLimit(User $user, Carbon $date): bool
    {
        $maxAllowed = max(1, floor($user->maximum / 4)); // 1/4 of max, minimum 1

        $cancelCount = Reservation::where('user_id', $user->id)
            ->where('status', 'cancelled_user')
            ->where('cancelled_at', '>=', $date->copy()->subDays(30))
            ->count();

        return $cancelCount < $maxAllowed;
    }

    public function isResourceAvailable(Resource $resource, Carbon $start, Carbon $end, bool $isFullDay): bool
    {
        $query = Reservation::where('resource_id', $resource->id)
            ->whereIn('status', ['active', 'released']);

        if ($isFullDay) {
            $query->whereDate('start_time', $start->toDateString());
        } else {
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orWhereBetween('end_time', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_time', '<=', $start)
                         ->where('end_time', '>=', $end);
                  });
            });
        }

        $existing = $query->get();

        if ($existing->isEmpty()) {
            return true;
        }

        // Check if there is an active reservation that does NOT have a matching released overlay.
        // Or simpler: a resource is occupied if there's any 'active' reservation
        // that is NOT superseded by a 'released' overlay.
        // Actually, the user stated: "Availability Check: A resource is 'Available' if (a) no reservation exists OR (b) the existing reservation has a matching released overlay for that specific date."

        $hasActive = false;
        $hasReleasedOverlay = false;

        foreach ($existing as $res) {
            if ($res->status === 'active') {
                $hasActive = true;
            }
            if ($res->status === 'released') {
                $hasReleasedOverlay = true;
            }
        }

        if ($hasActive && !$hasReleasedOverlay) {
            return false;
        }

        return true;
    }

    public function createReservation(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay): Reservation
    {
        if (!$user->isActive()) {
            throw new Exception("User account is not active.");
        }

        if (!$this->canUserBook($user, $resource->type)) {
            throw new Exception("User is not permitted to book this resource type.");
        }

        if (!$this->checkCancellationLimit($user, now())) {
            throw new Exception("Cancellation limit exceeded. Cannot book new resources.");
        }

        if (!$this->checkMonthlyLimit($user, $resource->type, $start)) {
            throw new Exception("Monthly booking limit reached for this resource type.");
        }

        // Handle booking window
        $policies = $this->getResourcePolicies($resource->type);
        $windowDays = $policies['booking_window_days'] ?? 1;
        $windowHours = $policies['booking_window_hours'] ?? 6;

        $maxBookingDate = now()->addDays($windowDays);
        if ($start->isAfter($maxBookingDate)) {
            throw new Exception("Booking date exceeds the allowed window.");
        }

        if ($start->isSameDay(now()) && now()->diffInHours($start, false) < $windowHours) {
           throw new Exception("Booking must be made at least {$windowHours} hours in advance.");
        }

        if (!$this->isResourceAvailable($resource, $start, $end, $isFullDay)) {
            throw new Exception("Resource is not available for the selected time.");
        }

        // Ensure user doesn't double book same type for same time
        $userConflictQuery = Reservation::where('user_id', $user->id)
            ->whereHas('resource', fn($q) => $q->where('type', $resource->type))
            ->whereIn('status', ['active']);

        if ($isFullDay) {
            $userConflictQuery->whereDate('start_time', $start->toDateString());
        } else {
            $userConflictQuery->where(function($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orWhereBetween('end_time', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                     $q2->where('start_time', '<=', $start)
                        ->where('end_time', '>=', $end);
                  });
            });
        }

        if ($userConflictQuery->exists()) {
             throw new Exception("User already has a booking for this resource type at this time.");
        }

        return DB::transaction(function () use ($user, $resource, $start, $end, $isFullDay) {
             return Reservation::create([
                 'user_id' => $user->id,
                 'resource_id' => $resource->id,
                 'start_time' => $start,
                 'end_time' => $end,
                 'is_full_day' => $isFullDay,
                 'status' => 'active',
             ]);
        });
    }

    public function cancelReservation(Reservation $reservation, User $user, string $reason = null): void
    {
        if ($reservation->status !== 'active') {
            throw new Exception("Reservation is not active.");
        }

        $isAdmin = $user->isAdmin();

        if (!$isAdmin && $reservation->user_id !== $user->id) {
            throw new Exception("Unauthorized to cancel this reservation.");
        }

        $status = $isAdmin ? 'cancelled_admin' : 'cancelled_user';

        $reservation->update([
            'status' => $status,
            'cancelled_by_id' => $user->id,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
    }
}
