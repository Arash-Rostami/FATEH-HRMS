<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Event;
use App\Services\Reservation\EventSyncService;
use App\Values\CalendarLayout;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class ResizeEventAction
{
    public function execute(int $id, int $durationMinutes, string $clientMtime): array
    {
        $userId = Auth::id();
        $key = "calendar-resize:{$userId}";

        if (RateLimiter::tooManyAttempts($key, 30)) {
            return ['ok' => false, 'reason' => 'rate_limited'];
        }

        RateLimiter::hit($key, 60);

        $event = Event::find($id);

        if (!$event || $event->user_id !== $userId) {
            return ['ok' => false, 'reason' => 'not_owner'];
        }

        $revertTo = ['durationMinutes' => $event->duration_minutes ?: Event::DEFAULT_DURATION_MINUTES];

        if (EventSyncService::isReservationEvent($event->description)) {
            return ['ok' => false, 'reason' => 'locked', 'revertTo' => $revertTo];
        }

        $startMinutes = ($event->date->hour * 60) + $event->date->minute;
        $maxDuration = min(Event::MAX_DURATION_MINUTES, CalendarLayout::DAY_END_MINUTES - $startMinutes);

        $validator = Validator::make(
            ['durationMinutes' => $durationMinutes],
            [
                'durationMinutes' => [
                    'required',
                    'integer',
                    'min:' . Event::MIN_DURATION_MINUTES,
                    'max:' . $maxDuration,
                ],
            ]
        );

        if ($validator->fails()) {
            return ['ok' => false, 'reason' => 'invalid_input', 'revertTo' => $revertTo];
        }

        try {
            $clientMtimeFmt = Carbon::parse($clientMtime)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return ['ok' => false, 'reason' => 'invalid_input', 'revertTo' => $revertTo];
        }

        return DB::transaction(function () use ($event, $durationMinutes, $clientMtimeFmt, $revertTo) {
            $locked = Event::where('id', $event->id)->lockForUpdate()->first();

            if (!$locked || $locked->user_id !== Auth::id()) {
                return ['ok' => false, 'reason' => 'not_owner'];
            }

            if (EventSyncService::isReservationEvent($locked->description)) {
                return ['ok' => false, 'reason' => 'locked', 'revertTo' => $revertTo];
            }

            if ($locked->updated_at->format('Y-m-d H:i:s') !== $clientMtimeFmt) {
                return ['ok' => false, 'reason' => 'stale', 'revertTo' => $revertTo];
            }

            $locked->duration_minutes = $durationMinutes;
            $locked->save();

            return [
                'ok' => true,
                'newMtime' => $locked->updated_at->toIso8601String(),
            ];
        });
    }
}