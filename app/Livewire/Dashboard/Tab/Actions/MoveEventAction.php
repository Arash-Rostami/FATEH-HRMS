<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Event;
use App\Rules\JalaliDateRule;
use App\Services\Reservation\EventSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Morilog\Jalali\Jalalian;

class MoveEventAction
{
    public function execute(int $id, ?string $dateJalali, ?string $timePart, string $clientMtime): array
    {
        $userId = Auth::id();
        $key = "calendar-move:{$userId}";

        if (RateLimiter::tooManyAttempts($key, 30)) {
            return ['ok' => false, 'reason' => 'rate_limited'];
        }

        RateLimiter::hit($key, 60);

        $event = Event::find($id);

        if (!$event || $event->user_id !== $userId) {
            return ['ok' => false, 'reason' => 'not_owner'];
        }

        $revertTo = [
            'dateJalali' => Jalalian::fromCarbon($event->date)->format('Y-m-d'),
            'timePart' => $event->date->format('H:i'),
        ];

        if (EventSyncService::isReservationEvent($event->description)) {
            return ['ok' => false, 'reason' => 'locked', 'revertTo' => $revertTo];
        }

        if ($dateJalali === null && $timePart === null) {
            return ['ok' => false, 'reason' => 'invalid_input', 'revertTo' => $revertTo];
        }

        $data = [];
        $rules = [];

        if ($dateJalali !== null) {
            $data['dateJalali'] = $dateJalali;
            $rules['dateJalali'] = ['required', 'string', new JalaliDateRule()];
        }

        if ($timePart !== null) {
            $data['timePart'] = $timePart;
            $rules['timePart'] = ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'];
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return ['ok' => false, 'reason' => 'invalid_input', 'revertTo' => $revertTo];
        }

        return DB::transaction(function () use ($event, $dateJalali, $timePart, $clientMtime) {
            $locked = Event::where('id', $event->id)->lockForUpdate()->first();

            if (!$locked || $locked->user_id !== Auth::id()) {
                return ['ok' => false, 'reason' => 'not_owner'];
            }

            if (EventSyncService::isReservationEvent($locked->description)) {
                return ['ok' => false, 'reason' => 'locked'];
            }

            try {
                $clientMtimeFmt = Carbon::parse($clientMtime)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                return ['ok' => false, 'reason' => 'invalid_input', 'revertTo' => [
                    'dateJalali' => Jalalian::fromCarbon($locked->date)->format('Y-m-d'),
                    'timePart' => $locked->date->format('H:i'),
                ]];
            }

            $currentMtime = $locked->updated_at->format('Y-m-d H:i:s');

            if ($currentMtime !== $clientMtimeFmt) {
                return [
                    'ok' => false,
                    'reason' => 'stale',
                    'revertTo' => [
                        'dateJalali' => Jalalian::fromCarbon($locked->date)->format('Y-m-d'),
                        'timePart' => $locked->date->format('H:i'),
                    ],
                ];
            }

            if ($dateJalali !== null) {
                $gregorian = Jalalian::fromFormat('Y-m-d', $dateJalali)->toCarbon()->format('Y-m-d');
                $locked->date_jalali = $gregorian;
            }

            if ($timePart !== null) {
                $locked->date_time_part = $timePart;
            }

            $locked->save();

            return [
                'ok' => true,
                'newMtime' => $locked->updated_at->toIso8601String(),
            ];
        });
    }
}