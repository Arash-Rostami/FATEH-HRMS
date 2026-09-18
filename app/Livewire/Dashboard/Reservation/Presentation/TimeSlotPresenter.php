<?php

namespace App\Livewire\Dashboard\Reservation\Presentation;

use Carbon\Carbon;

class TimeSlotPresenter
{
    public static function allowedHoursBounds(?array $allowed): array
    {
        $start = Carbon::parse($allowed['start'] ?? '08:00');
        $end = Carbon::parse($allowed['end'] ?? '20:00');

        if ($end <= $start) {
            $start = Carbon::parse('08:00');
            $end = Carbon::parse('20:00');
        }

        return [$start, $end];
    }

    public static function slots(?array $allowedHours): array
    {
        [$start, $end] = self::allowedHoursBounds($allowedHours);

        $slots = [];
        for ($cursor = $start->copy(); $cursor < $end; $cursor->addMinutes(30)) {
            $slots[] = $cursor->format('H:i');
        }

        return $slots;
    }

    public static function startSlotMeta(array $slots, bool $isFullDay, string $date, ?int $minNoticeHours): array
    {
        if ($isFullDay || empty($slots)) {
            return ['states' => [], 'first' => null];
        }

        $today = Carbon::parse($date)->startOfDay();

        if (! $today->isSameDay(Carbon::now()->startOfDay())) {
            return ['states' => array_fill_keys($slots, 'ok'), 'first' => $slots[0]];
        }

        $now = Carbon::now();
        $cutoff = $now->copy()->addHours($minNoticeHours ?? 0);
        $states = [];
        $first = null;

        foreach ($slots as $t) {
            $slot = Carbon::parse("{$date} {$t}");

            if ($slot < $now) {
                $st = 'past';
            } elseif ($slot < $cutoff) {
                $st = 'soon';
            } else {
                $st = 'ok';
            }

            $states[$t] = $st;
            if ($st === 'ok' && $first === null) {
                $first = $t;
            }
        }

        return ['states' => $states, 'first' => $first];
    }

    public static function durationBounds(mixed $min, mixed $max): ?string
    {
        $min = $min !== null ? (int) $min : null;
        $max = $max !== null ? (int) $max : null;

        if ($min === null && $max === null) {
            return null;
        }

        if ($min !== null && $max !== null) {
            return 'مدت مجاز: ' . convertToPersian((string) $min) . ' تا ' . convertToPersian((string) $max) . ' دقیقه';
        }

        if ($min !== null) {
            return 'حداقل مدت: ' . convertToPersian((string) $min) . ' دقیقه';
        }

        return 'حداکثر مدت: ' . convertToPersian((string) $max) . ' دقیقه';
    }

    public static function selectedDuration(string $date, string $startTime, string $endTime, mixed $min, mixed $max): array
    {
        $start = Carbon::parse("{$date} {$startTime}");
        $end = Carbon::parse("{$date} {$endTime}");
        $minutes = (int) $start->diffInMinutes($end);

        $valid = $minutes > 0
            && ($min === null || $minutes >= (int) $min)
            && ($max === null || $minutes <= (int) $max);

        if ($minutes <= 0) {
            $text = 'زمان پایان باید بعد از شروع باشد';
        } else {
            $text = 'مدت انتخابی: ' . self::humanizeMinutes($minutes);
        }

        return ['minutes' => $minutes, 'text' => $text, 'valid' => $valid];
    }

    private static function humanizeMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return convertToPersian((string) $minutes) . ' دقیقه';
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($m === 0) {
            return convertToPersian((string) $h) . ' ساعت';
        }

        return convertToPersian((string) $h) . ' ساعت و ' . convertToPersian((string) $m) . ' دقیقه';
    }
}
