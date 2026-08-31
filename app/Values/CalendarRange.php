<?php

namespace App\Values;

use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

final readonly class CalendarRange
{
    public const WEEK_DAY_LABELS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public string $view,
        public string $navigationDate,
    ) {}

    public static function fromNavigation(string $navigationDate, string $view): self
    {
        $nav = Jalalian::fromFormat('Y-m-d', $navigationDate)->toCarbon()->startOfDay();

        return match ($view) {
            'week' => self::buildWeek($nav, $navigationDate),
            'day' => new self(
                (clone $nav)->startOfDay(),
                (clone $nav)->endOfDay(),
                $view,
                $navigationDate,
            ),
            default => self::buildMonth($nav, $navigationDate),
        };
    }

    public function jalaliLabel(): string
    {
        $startJ = Jalalian::fromCarbon($this->start);
        $endJ = Jalalian::fromCarbon($this->end);

        return match ($this->view) {
            'week' => $startJ->getMonth() === $endJ->getMonth() && $startJ->getYear() === $endJ->getYear()
                ? sprintf('%s – %s %s', $startJ->format('d'), $endJ->format('d'), $startJ->format('F Y'))
                : sprintf('%s – %s', $startJ->format('d F Y'), $endJ->format('d F Y')),
            'day' => $startJ->format('d F Y'),
            default => $startJ->format('F Y'),
        };
    }

    public function days(): array
    {
        $days = [];
        $cursor = $this->start->copy()->startOfDay();
        $end = $this->end->copy()->startOfDay();

        while ($cursor <= $end) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        return $days;
    }

    public function contains(Carbon $date): bool
    {
        $day = $date->copy()->startOfDay();

        return $day >= $this->start->copy()->startOfDay()
            && $day <= $this->end->copy()->startOfDay();
    }

    public function weekdayOffset(): int
    {
        if ($this->view !== 'month') {
            return 0;
        }

        return Jalalian::fromCarbon($this->start)->getDayOfWeek();
    }

    public static function advanceMonths(string $navigationDate, int $deltaMonths): string
    {
        try {
            $j = Jalalian::fromFormat('Y-m-d', $navigationDate);

            return $deltaMonths >= 0
                ? $j->addMonths($deltaMonths)->format('Y-m-d')
                : $j->subMonths(abs($deltaMonths))->format('Y-m-d');
        } catch (\Throwable) {
            return Jalalian::now()->format('Y-m-d');
        }
    }

    private static function buildMonth(Carbon $nav, string $navigationDate): self
    {
        $j = Jalalian::fromCarbon($nav);
        $first = (new Jalalian($j->getYear(), $j->getMonth(), 1))->toCarbon()->startOfDay();
        $last = (new Jalalian($j->getYear(), $j->getMonth(), $j->getMonthDays()))->toCarbon()->endOfDay();

        return new self($first, $last, 'month', $navigationDate);
    }

    private static function buildWeek(Carbon $nav, string $navigationDate): self
    {
        $offset = Jalalian::fromCarbon($nav)->getDayOfWeek();
        $start = (clone $nav)->subDays($offset)->startOfDay();
        $end = (clone $start)->addDays(6)->endOfDay();

        return new self($start, $end, 'week', $navigationDate);
    }
}