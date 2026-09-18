<?php

namespace App\Livewire\Dashboard\Reservation\Presentation;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Morilog\Jalali\Jalalian;
use Throwable;

class AvantgardePresenter
{
    public static function historyBucket(Reservation $reservation): string
    {
        if (in_array($reservation->status, [
            ReservationStatus::CancelledUser->value,
            ReservationStatus::CancelledAdmin->value], true)) {
            return 'cancelled';
        }

        if ($reservation->status === ReservationStatus::Released->value) {
            return 'released';
        }

        return $reservation->end_time < now() ? 'previous' : 'upcoming';
    }

    public static function canvasTabs(): array
    {
        return [
            ['id' => 'booking', 'icon' => 'event_available', 'label' => 'رزرو'],
            ['id' => 'grid', 'icon' => 'schedule', 'label' => 'تاریخچه من'],
        ];
    }

    public static function weekDayLabels(): array
    {
        return ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
    }

    public static function activeTypeMeta(array $tabs, string $activeTab): ?array
    {
        foreach ($tabs as $tab) {
            if ($tab['id'] === $activeTab) {
                return $tab;
            }
        }

        return null;
    }

    public static function calendarCells(int $year, int $month, array $availableDates, string $selectedDate, ?array $allowedDays = null): array
    {
        $availableByValue = array_column($availableDates, null, 'value');
        $cells = [];

        try {
            $firstDay = new Jalalian($year, $month, 1);
            $daysInMonth = $firstDay->getMonthDays();
            $lead = $firstDay->getDayOfWeek();

            for ($i = 0; $i < $lead; $i++) {
                $cells[] = null;
            }

            $today = now()->toDateString();
            $cursor = $firstDay->toCarbon()->startOfDay();
            for ($d = 1; $d <= $daysInMonth; $d++, $cursor->addDay()) {
                $value = $cursor->toDateString();
                $row = $availableByValue[$value] ?? null;
                $reason = null;

                if ($row === null) {
                    if ($value < $today) {
                        $reason = 'past';
                    } elseif ($allowedDays !== null && ! in_array(strtolower($cursor->englishDayOfWeek), $allowedDays, true)) {
                        $reason = 'day_off';
                    } else {
                        $reason = 'window';
                    }
                }

                $cells[] = [
                    'day' => $d,
                    'value' => $value,
                    'available' => $row !== null,
                    'isToday' => $row['isToday'] ?? ($value === $today),
                    'selected' => $selectedDate === $value,
                    'reason' => $reason,
                ];
            }
        } catch (Throwable $e) {
            return [];
        }

        return $cells;
    }

    public static function blockedDayLabels(): array
    {
        return [
            'past' => 'روز گذشته',
            'day_off' => 'روز غیرمجاز طبق قوانین رزرو',
            'window' => 'خارج از بازه رزرو',
        ];
    }

    public static function timeRail(array $slots, string $startTime, string $endTime, array $busySegments, ?string $selectedDate = null): array
    {
        $slotCount = count($slots);
        $lastIdx = max($slotCount - 1, 0);

        $startIdx = array_search($startTime, $slots, true);
        $endIdx = array_search($endTime, $slots, true);
        $startIdx = $startIdx === false ? 0 : $startIdx;
        $endIdx = $endIdx === false ? $lastIdx : $endIdx;

        $busyRanges = [];
        $busyIdx = [];
        foreach ($slotCount > 0 ? $busySegments : [] as $seg) {
            $s = 0;
            $e = 0;
            foreach ($slots as $i => $slot) {
                if ($slot <= $seg['start']) {
                    $s = $i;
                }
                if ($slot < $seg['end']) {
                    $e = $i;
                }
            }

            if ($seg['end'] <= $slots[0] || $seg['start'] > $slots[$lastIdx] || $e < $s) {
                continue;
            }

            $busyRanges[] = [
                'from' => $slotCount > 1 ? $s / $lastIdx * 100 : 0,
                'to' => $slotCount > 1 ? $e / $lastIdx * 100 : 100,
            ];
            $busyIdx[] = [$s, $e];
        }

        $nowIdx = null;
        if ($selectedDate !== null && $selectedDate === now()->toDateString()) {
            $nowTime = now()->format('H:i');
            foreach ($slots as $i => $slot) {
                if ($slot <= $nowTime) {
                    $nowIdx = $i;
                }
            }
        }

        $tickIdx = $slotCount > 1
            ? array_values(array_unique(array_map(fn ($f) => (int) round($f * $lastIdx), [0, 0.25, 0.5, 0.75, 1])))
            : [];

        $ticks = [];
        foreach ($tickIdx as $ti) {
            $ticks[] = [
                'slot' => $slots[$ti],
                'pct' => $slotCount > 1 ? $ti / $lastIdx * 100 : 0,
            ];
        }

        return [
            'slots' => $slots,
            'slotCount' => $slotCount,
            'startIdx' => $startIdx,
            'endIdx' => $endIdx,
            'busyRanges' => $busyRanges,
            'busyIdx' => $busyIdx,
            'nowIdx' => $nowIdx,
            'ticks' => $ticks,
        ];
    }
}
