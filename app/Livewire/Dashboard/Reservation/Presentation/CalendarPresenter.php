<?php

namespace App\Livewire\Dashboard\Reservation\Presentation;

use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Throwable;

class CalendarPresenter
{
    public static function availableDates(int $currentYear, int $currentMonth, ?int $windowDays, ?array $allowedDays): array
    {
        $dates = [];
        $now = Carbon::now()->startOfDay();

        if ($windowDays === null) {
            $date = $now->copy();
            for ($i = 0; $i < 21; $i++, $date->addDay()) {
                if ($allowedDays !== null && !in_array(strtolower($date->englishDayOfWeek), $allowedDays, true)) {
                    continue;
                }
                $j = Jalalian::fromCarbon($date);
                $dates[] = [
                    'value' => $date->toDateString(),
                    'day' => $j->format('l'),
                    'date' => $j->format('d'),
                    'month' => $j->format('F'),
                    'isToday' => $date->isSameDay($now),
                ];
            }
            return $dates;
        }

        $horizon = Carbon::now()->addDays($windowDays)->endOfDay();

        try {
            $daysInMonth = (new Jalalian($currentYear, $currentMonth, 1))->getMonthDays();
            $date = (new Jalalian($currentYear, $currentMonth, 1))->toCarbon()->startOfDay();
        } catch (Throwable $e) {
            return [];
        }

        for ($d = 1; $d <= $daysInMonth; $d++, $date->addDay()) {
            if ($date < $now) {
                continue;
            }
            if ($date > $horizon) {
                break;
            }
            if ($allowedDays !== null && !in_array(strtolower($date->englishDayOfWeek), $allowedDays, true)) {
                continue;
            }
            $j = Jalalian::fromCarbon($date);
            $dates[] = [
                'value' => $date->toDateString(),
                'day' => $j->format('l'),
                'date' => $j->format('d'),
                'month' => $j->format('F'),
                'isToday' => $date->isSameDay($now),
            ];
        }

        return $dates;
    }

    public static function canPrevMonth(int $currentYear, int $currentMonth): bool
    {
        $now = Jalalian::now();

        if ($currentYear !== $now->getYear()) {
            return $currentYear > $now->getYear();
        }

        return $currentMonth > $now->getMonth();
    }

    public static function canNextMonth(int $currentYear, int $currentMonth, ?int $windowDays): bool
    {
        if ($windowDays === null) {
            return false;
        }

        $horizon = Carbon::now()->addDays($windowDays)->startOfDay();
        $year = $currentYear;
        $month = $currentMonth + 1;

        if ($month > 12) {
            $month = 1;
            $year++;
        }

        try {
            $nextFirst = (new Jalalian($year, $month, 1))->toCarbon()->startOfDay();
        } catch (Throwable $e) {
            return false;
        }

        return $nextFirst <= $horizon;
    }

    public static function currentMonthName(int $currentYear, int $currentMonth): string
    {
        try {
            return (new Jalalian($currentYear, $currentMonth, 1))->format('F Y');
        } catch (Throwable $e) {
            return '';
        }
    }
}
