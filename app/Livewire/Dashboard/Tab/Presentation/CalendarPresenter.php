<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Models\Event;
use App\Models\Profile;
use App\Services\HolidayService;
use App\Services\Reservation\EventSyncService;
use App\Values\CalendarLayout;
use App\Values\CalendarRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Morilog\Jalali\Jalalian;
use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Models\Reservation;
use Carbon\Carbon;

class CalendarPresenter
{
    private const HOUR_HEIGHT = 60;
    private const START_HOUR = CalendarLayout::GRID_START_MINUTES / 60;
    private const END_HOUR = CalendarLayout::DAY_END_MINUTES / 60;
    private const WEEK_DAY_LABELS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
    private const ICON_BY_TYPE = ['holiday' => 'event_busy', 'birthday' => 'cake', 'anniversary' => 'celebration'];

    private array $monthDaysCache = [];
    private array $rangeCache = [];
    private ?Collection $profilesWithDatesCache = null;
    private ?array $holidaysAllCache = null;
    private ?Collection $userReservationsCache = null;
    private ?array $userReservationsRange = null;

    public function activeDate(string $selectedDate): array
    {
        try {
            $jalali = Jalalian::fromFormat('Y-m-d', $selectedDate);
        } catch (Throwable $e) {
            $jalali = Jalalian::now();
        }

        return [
            'jalali' => $jalali->format('l، d F Y'),
            'gregorian' => $jalali->toCarbon()->format('D, d M Y'),
            'isToday' => $jalali->format('Y-m-d') === $this->todayKey(),
        ];
    }

    public function monthDays(string $navDateYmd, string $selectedDate): array
    {
        try {
            $j = Jalalian::fromFormat('Y-m-d', $navDateYmd);
        } catch (Throwable $e) {
            return [];
        }

        return $this->buildMonthDays($j->getYear(), $j->getMonth(), $selectedDate);
    }

    public function currentMonthName(string $navDateYmd): string
    {
        try {
            $j = Jalalian::fromFormat('Y-m-d', $navDateYmd);
            return (new Jalalian($j->getYear(), $j->getMonth(), 1))->format('F Y');
        } catch (Throwable $e) {
            return '';
        }
    }

    public function rangeLabel(string $navDateYmd, string $view): string
    {
        try {
            return $this->range($navDateYmd, $view)->jalaliLabel();
        } catch (Throwable $e) {
            return '';
        }
    }

    public function rangeEvents(string $navDateYmd, string $view): array
    {
        try {
            $range = $this->range($navDateYmd, $view);
        } catch (Throwable $e) {
            return ['byDay' => [], 'allDayEntries' => [], 'spanningReservations' => []];
        }

        $authId = $this->authId();

        $events = Event::query()
            ->with(['shares' => fn($q) => $q->select('id', 'user_id', 'event_id', 'shared_by')->with('sharer:id,name')])
            ->whereBetween('date', [$range->start, $range->end])
            ->where(function ($q) use ($authId) {
                $q->where('user_id', $authId)
                    ->orWhere('private', false)
                    ->orWhereHas('shares', fn($sq) => $sq->where('user_id', $authId));
            })
            ->get();

        $byDay = [];

        foreach ($events as $event) {
            $eventJalali = Jalalian::fromCarbon($event->date);
            $key = $eventJalali->format('Y-m-d');
            $isShared = $event->user_id !== $authId
                && $event->shares->contains('user_id', $authId);
            $isReservation = EventSyncService::isReservationEvent($event->description);

            $byDay[$key][] = [
                'id' => $event->id,
                'type' => 'event',
                'is_full_day' => false,
                'title' => $event->title,
                'description' => $event->description,
                'time' => $eventJalali->format('H:i'),
                'start_minutes' => ($event->date->hour * 60) + $event->date->minute,
                'duration_minutes' => $event->duration_minutes ?: Event::DEFAULT_DURATION_MINUTES,
                'is_owner' => $event->user_id === $authId,
                'private' => $event->private,
                'is_shared' => $isShared,
                'shared_by_name' => $isShared
                    ? $event->shares->firstWhere('user_id', $authId)?->sharer?->name
                    : null,
                'is_reservation_linked' => $isReservation,
                'reservation_id' => EventSyncService::reservationIdFrom($event->description),
                'remind_hours' => $event->remind_hours,
                'locked' => $isReservation || $event->user_id !== $authId,
                'mtime' => $event->updated_at?->toIso8601String(),
            ];
        }

        $existingResIds = [];
        foreach ($byDay as $pills) {
            foreach ($pills as $p) {
                if (!empty($p['is_reservation_linked']) && !empty($p['reservation_id'])) {
                    $existingResIds[$p['reservation_id']] = true;
                }
            }
        }
        foreach ($this->userReservationsByDay($range->start, $range->end) as $key => $reservations) {
            foreach ($reservations as $reservation) {
                if (isset($existingResIds[$reservation->id])) {
                    continue;
                }
                $byDay[$key][] = $this->reservationPill($reservation);
            }
        }

        $spanningReservations = [];
        if ($view === 'week') {
            foreach ($this->userReservationsSpanning($range->start, $range->end) as $spanning) {
                if (isset($existingResIds[$spanning['reservation_id']])) {
                    continue;
                }
                $spanningReservations[] = $spanning;
            }
        }

        $gridStart = CalendarLayout::GRID_START_MINUTES;
        $gridEnd = CalendarLayout::DAY_END_MINUTES;
        $fullDayMinutes = $gridEnd - $gridStart;

        foreach ($byDay as $key => $pills) {
            if ($view === 'day') {
                $timed = array_filter(
                    $pills,
                    fn($p) => empty($p['is_full_day'])
                        && empty($p['is_multi_day'])
                        && ($p['start_minutes'] ?? 0) >= $gridStart
                        && ($p['start_minutes'] ?? 0) < $gridEnd
                );
                $allDayRes = array_filter(
                    $pills,
                    fn($p) => !empty($p['is_reservation_linked'])
                        && (!empty($p['is_full_day']) || !empty($p['is_multi_day']))
                );
                $allDayRes = array_map(
                    fn($p) => array_merge($p, ['start_minutes' => $gridStart, 'duration_minutes' => $fullDayMinutes]),
                    array_values($allDayRes)
                );
                $outOfGrid = array_filter(
                    $pills,
                    fn($p) => empty($p['is_full_day'])
                        && empty($p['is_multi_day'])
                        && (($p['start_minutes'] ?? 0) < $gridStart || ($p['start_minutes'] ?? 0) >= $gridEnd)
                );
                $byDay[$key] = array_merge(
                    CalendarLayout::pack(array_merge(array_values($timed), $allDayRes)),
                    array_values($outOfGrid)
                );
            } else {
                $banner = array_filter(
                    $pills,
                    fn($p) => !empty($p['is_reservation_linked'])
                        && empty($p['is_multi_day'])
                        && (
                            !empty($p['is_full_day'])
                            || ($p['start_minutes'] ?? 0) < $gridStart
                            || ($p['start_minutes'] ?? 0) >= $gridEnd
                        )
                );
                $timed = array_filter(
                    $pills,
                    fn($p) => empty($p['is_full_day'])
                        && empty($p['is_multi_day'])
                        && ($p['start_minutes'] ?? 0) >= $gridStart
                        && ($p['start_minutes'] ?? 0) < $gridEnd
                );
                $byDay[$key] = array_merge(
                    CalendarLayout::pack(array_values($timed)),
                    array_values($banner)
                );
            }
        }

        return [
            'byDay' => $byDay,
            'allDayEntries' => $this->buildAllDayEntries($range),
            'spanningReservations' => $spanningReservations,
        ];
    }

    public function weekLabels(): array
    {
        return self::WEEK_DAY_LABELS;
    }

    public function gridData(string $navigationDate, string $view, array $rangeEvents): array
    {
        $days = $this->daysFor($navigationDate, $view);
        $byDay = $rangeEvents['byDay'] ?? [];
        $allByDay = $this->groupAllDayByDate($rangeEvents['allDayEntries'] ?? []);
        $todayKey = $this->todayKey();
        $gridHeight = (self::END_HOUR - self::START_HOUR) * self::HOUR_HEIGHT;

        $hourOffsets = [];
        $hourLabels = [];
        for ($h = self::START_HOUR; $h < self::END_HOUR; $h++) {
            $hourOffsets[$h] = ($h - self::START_HOUR) * self::HOUR_HEIGHT;
            $hourLabels[$h] = convertToPersian(str_pad((string) $h, 2, '0', STR_PAD_LEFT));
        }

        $allReservations = [];
        foreach ($byDay as $pills) {
            foreach ($pills as $p) {
                if (!empty($p['is_reservation_linked']) && !isset($p['col'])) {
                    $allReservations[] = $p;
                }
            }
        }

        $daysMeta = [];
        foreach ($days as $day) {
            $jalali = Jalalian::fromCarbon($day);
            $jKey = $jalali->format('Y-m-d');
            $dow = $jalali->getDayOfWeek();
            $isToday = $jKey === $todayKey;
            $isFriday = $dow === 6;

            $dayPills = $byDay[$jKey] ?? [];
            $enriched = [];
            foreach ($dayPills as $p) {
                $enriched[] = $view === 'day'
                    ? $this->withGeometry($p, $gridHeight)
                    : (empty($p['is_full_day']) ? $this->withGeometry($p, $gridHeight) : $p);
            }

            $daysMeta[] = [
                'jKey' => $jKey,
                'weekLabel' => self::WEEK_DAY_LABELS[$dow] ?? 'ش',
                'dayNum' => convertToPersian($jalali->getDay()),
                'isFriday' => $isFriday,
                'isToday' => $isToday,
                'dayAllDay' => $allByDay[$jKey] ?? [],
                'dayPills' => $enriched,
                'startIso' => $isToday ? $day->copy()->setTime(self::START_HOUR, 0)->toIso8601String() : null,
            ];
        }

        if (empty($daysMeta) && $view === 'day') {
            $daysMeta[] = $this->emptyDayMeta($navigationDate, $todayKey);
        }

        $spanningReservations = [];
        if ($view === 'week' && !empty($rangeEvents['spanningReservations'])) {
            $jKeyIndex = array_flip(array_map(fn($m) => $m['jKey'], $daysMeta));
            $totalCols = count($daysMeta) ?: 1;
            foreach ($rangeEvents['spanningReservations'] as $s) {
                $startIdx = $jKeyIndex[$s['start_jkey']] ?? 0;
                $endIdx = $jKeyIndex[$s['end_jkey']] ?? ($totalCols - 1);
                if ($endIdx < $startIdx) {
                    $endIdx = $startIdx;
                }
                $s['col_start'] = $startIdx;
                $s['col_span'] = $endIdx - $startIdx + 1;
                $spanningReservations[] = $s;
            }
        }

        return [
            'daysMeta' => $daysMeta,
            'hourOffsets' => $hourOffsets,
            'hourLabels' => $hourLabels,
            'gridHeight' => $gridHeight,
            'hourHeight' => self::HOUR_HEIGHT,
            'startHour' => self::START_HOUR,
            'endHour' => self::END_HOUR,
            'iconByType' => self::ICON_BY_TYPE,
            'allReservations' => $allReservations,
            'spanningReservations' => $spanningReservations,
        ];
    }

    public function agendaItems(string $navigationDate, string $view, array $rangeEvents): array
    {
        $days = $this->daysFor($navigationDate, $view);
        $byDay = $rangeEvents['byDay'] ?? [];
        $allByDay = $this->groupAllDayByDate($rangeEvents['allDayEntries'] ?? []);

        $result = [];
        foreach ($days as $day) {
            $jalali = Jalalian::fromCarbon($day);
            $jKey = $jalali->format('Y-m-d');

            $items = [];
            foreach ($allByDay[$jKey] ?? [] as $e) {
                $items[] = [
                    'id' => $e['id'],
                    'type' => $e['type'],
                    'title' => $e['title'],
                    'time' => '00:00',
                    'clickable' => false,
                    'icon' => self::ICON_BY_TYPE[$e['type']] ?? 'event',
                ];
            }
            foreach ($byDay[$jKey] ?? [] as $p) {
                $items[] = [
                    'id' => $p['id'],
                    'type' => 'event',
                    'title' => $p['title'],
                    'time' => $p['time'],
                    'clickable' => $p['is_owner'] && empty($p['is_reservation_linked']),
                    'icon' => empty($p['is_reservation_linked']) ? 'event' : 'event_seat',
                ];
            }
            usort($items, fn($a, $b) => $a['time'] <=> $b['time']);

            $result[] = [
                'jKey' => $jKey,
                'label' => convertToPersian($jalali->format('l، d F Y')),
                'items' => $items,
            ];
        }

        return $result;
    }

    public function selectedDayEvents(string $selectedDate): Collection
    {
        if (!$selectedDate) {
            return collect();
        }

        try {
            $gregorianDate = Jalalian::fromFormat('Y-m-d', $selectedDate)->toCarbon();
            $month = $gregorianDate->month;
            $day = $gregorianDate->day;
        } catch (Throwable $e) {
            return collect();
        }

        $authId = $this->authId();

        $holidays = collect(HolidayService::getHolidaysForDate($selectedDate))
            ->map(fn($holiday, $index) => [
                'id' => 'holiday-' . $index,
                'type' => 'holiday',
                'title' => $holiday['title'],
                'description' => $holiday['type'] . (($holiday['hijri'] ?? null) ? ' · ' . $holiday['hijri'] : ''),
                'time' => '00:00',
                'is_owner' => false,
                'private' => false,
            ]);

        $events = Event::query()
            ->with(['shares' => fn($q) => $q->select('id', 'user_id', 'event_id', 'shared_by')->with('sharer:id,name')])
            ->whereDate('date', $gregorianDate)
            ->where(function ($q) use ($authId) {
                $q->where('user_id', $authId)
                    ->orWhere('private', false)
                    ->orWhereHas('shares', fn($sq) => $sq->where('user_id', $authId));
            })
            ->latest('date')
            ->get()
            ->map(function ($event) use ($authId) {
                $isShared = $event->user_id !== $authId
                    && $event->shares->contains('user_id', $authId);

                return [
                    'id' => $event->id,
                    'type' => 'event',
                    'is_full_day' => false,
                    'title' => $event->title,
                    'description' => $event->description,
                    'time' => Jalalian::fromCarbon($event->date)->format('H:i'),
                    'start_minutes' => ($event->date->hour * 60) + $event->date->minute,
                    'duration_minutes' => $event->duration_minutes ?: Event::DEFAULT_DURATION_MINUTES,
                    'is_owner' => $event->user_id === $authId,
                    'private' => $event->private,
                    'is_shared' => $isShared,
                    'shared_by_name' => $isShared
                        ? $event->shares->firstWhere('user_id', $authId)?->sharer?->name
                        : null,
                    'is_reservation_linked' => EventSyncService::isReservationEvent($event->description),
                    'reservation_id' => EventSyncService::reservationIdFrom($event->description),
                    'remind_hours' => $event->remind_hours,
                ];
            });

        $profiles = $this->getProfilesWithDates();

        $birthdays = $profiles->filter(fn($p) => $p->birthdate?->month === $month && $p->birthdate?->day === $day)
            ->map(fn($p) => [
                'id' => 'birthday-' . $p->id,
                'type' => 'birthday',
                'title' => 'تولد ' . ($p->user->name ?? 'کاربر'),
                'description' => 'تولد مبارک!',
                'time' => '00:00',
                'is_owner' => false,
                'private' => false,
                'avatar' => $p->getImageUrl(),
            ]);

        $anniversaries = $profiles->filter(fn($p) => $p->start_date?->month === $month && $p->start_date?->day === $day)
            ->map(function ($p) use ($gregorianDate) {
                $years = $p->start_date->diffInYears($gregorianDate);
                return [
                    'id' => 'anniversary-' . $p->id,
                    'type' => 'anniversary',
                    'title' => ($years > 0 ? $years . 'مین ' : '') . 'سالگرد همکاری ' . ($p->user->name ?? 'کاربر'),
                    'description' => 'سالگرد همکاری مبارک!',
                    'time' => '00:00',
                    'is_owner' => false,
                    'private' => false,
                    'avatar' => $p->getImageUrl(),
                ];
            });

        $existingResIds = [];
        foreach ($events as $ev) {
            if (!empty($ev['is_reservation_linked']) && !empty($ev['reservation_id'])) {
                $existingResIds[$ev['reservation_id']] = true;
            }
        }

        $monthStart = $gregorianDate->copy()->startOfMonth();
        $monthEnd = $gregorianDate->copy()->endOfMonth();
        $dayReservations = collect($this->userReservationsByDay($monthStart, $monthEnd)[$selectedDate] ?? [])
            ->filter(fn(Reservation $r) => !isset($existingResIds[$r->id]))
            ->map(fn(Reservation $r) => $this->reservationPill($r));

        return collect()
            ->concat($holidays)
            ->concat($events)
            ->concat($dayReservations)
            ->concat($birthdays)
            ->concat($anniversaries);
    }

    public function eventBlockData(array $event): array
    {
        $locked = !empty($event['locked']);
        $isReservation = !empty($event['is_reservation_linked']);
        $draggable = $event['is_owner'] && !$locked && !$isReservation;
        $topPx = (int) ($event['top'] ?? 0);
        $heightPx = (int) ($event['height'] ?? 30);
        $leftPct = (float) ($event['left_pct'] ?? 0);
        $widthPct = (float) ($event['width_pct'] ?? 100);
        $isFullDayBlock = !empty($event['is_full_day']) || !empty($event['is_multi_day']);
        if ($isFullDayBlock) {
            $rangeLabel = convertToPersian('تمام روز');
        } else {
            $showEndTime = $event['duration_minutes'] > 30;
            $cappedDuration = min($event['duration_minutes'], 1440 - $event['start_minutes']);
            $endMinutes = $event['start_minutes'] + $cappedDuration;
            $endTime = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);
            $rangeLabel = $showEndTime
                ? convertToPersian($event['time']) . '–' . convertToPersian($endTime)
                : convertToPersian($event['time']);
        }

        return [
            'locked' => $locked,
            'isReservation' => $isReservation,
            'draggable' => $draggable,
            'topPx' => $topPx,
            'heightPx' => $heightPx,
            'leftPct' => $leftPct,
            'widthPct' => $widthPct,
            'rangeLabel' => $rangeLabel,
        ];
    }

    public function dayEventRangeLabel(array $event): string
    {
        $rangeLabel = convertToPersian($event['time']);
        if (($event['type'] ?? '') === 'event' && $event['duration_minutes'] > 30) {
            $cappedDuration = min($event['duration_minutes'], 1440 - $event['start_minutes']);
            $endMinutes = $event['start_minutes'] + $cappedDuration;
            $rangeLabel .= '–' . convertToPersian(sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60));
        }

        return $rangeLabel;
    }

    public function miniMonthData(string $miniMonthDate): array
    {
        try {
            $miniJ = Jalalian::fromFormat('Y-m-d', $miniMonthDate);
            $miniLabel = $miniJ->format('F Y');
            $prevMonth = $miniJ->subMonths(1)->format('Y-m-d');
            $nextMonth = $miniJ->addMonths(1)->format('Y-m-d');
            $prevYear = $miniJ->subYears(1)->format('Y-m-d');
            $nextYear = $miniJ->addYears(1)->format('Y-m-d');
            $yearLabel = $miniJ->format('Y');
            $monthLabel = $miniJ->format('F');
        } catch (Throwable $e) {
            $miniLabel = '';
            $prevMonth = $miniMonthDate;
            $nextMonth = $miniMonthDate;
            $prevYear = $miniMonthDate;
            $nextYear = $miniMonthDate;
            $yearLabel = '';
            $monthLabel = '';
        }

        return [
            'weekDays' => $this->weekLabels(),
            'miniLabel' => $miniLabel,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'prevYear' => $prevYear,
            'nextYear' => $nextYear,
            'yearLabel' => $yearLabel,
            'monthLabel' => $monthLabel,
        ];
    }

    private function authId(): ?int
    {
        return once(fn () => Auth::id());
    }

    private function userReservationsByDay(Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        if ($this->userReservationsCache === null
            || $this->userReservationsRange === null
            || $start < $this->userReservationsRange[0]
            || $end > $this->userReservationsRange[1]
        ) {
            $this->userReservationsCache = Reservation::query()
                ->forUser($this->authId() ?? 0)
                ->where('status', ReservationStatus::Active->value)
                ->where('end_time', '>=', $start)
                ->where('start_time', '<=', $end)
                ->with('resource:id,name,type')
                ->get();
            $this->userReservationsRange = [$start, $end];
        }

        $byDay = [];
        foreach ($this->userReservationsCache as $reservation) {
            $resStart = $reservation->start_time?->startOfDay();
            $resEnd = $reservation->end_time?->endOfDay();
            if (!$resStart || !$resEnd) {
                continue;
            }
            $cursor = $resStart->greaterThan($start) ? $resStart->copy() : $start->copy();
            $loopEnd = $resEnd->lessThan($end) ? $resEnd : $end;
            while ($cursor <= $loopEnd) {
                $byDay[Jalalian::fromCarbon($cursor)->format('Y-m-d')][] = $reservation;
                $cursor->addDay();
            }
        }

        return $byDay;
    }

    private function userReservationsSpanning(Carbon $start, Carbon $end): array
    {
        $this->userReservationsByDay($start, $end);

        $spanning = [];
        foreach ($this->userReservationsCache as $reservation) {
            if (!$reservation->start_time || !$reservation->end_time || $reservation->start_time->isSameDay($reservation->end_time)) {
                continue;
            }
            $spanning[] = $this->spanningPill($reservation);
        }

        return $spanning;
    }

    private function spanningPill(Reservation $reservation): array
    {
        $type = $reservation->resource?->type;
        $resolved = $type !== null ? ResourceType::tryFrom($type) : null;
        $typeLabel = $resolved?->getLabel() ?? $type ?? 'منبع نامشخص';
        $resourceName = $reservation->resource?->name ?? 'منبع نامشخص';

        return [
            'id' => 'res-' . $reservation->id,
            'reservation_id' => $reservation->id,
            'title' => "{$typeLabel} · {$resourceName}",
            'is_full_day' => (bool) $reservation->is_full_day,
            'start_jkey' => Jalalian::fromCarbon($reservation->start_time->copy()->startOfDay())->format('Y-m-d'),
            'end_jkey' => Jalalian::fromCarbon($reservation->end_time->copy()->startOfDay())->format('Y-m-d'),
        ];
    }

    private function reservationPill(Reservation $reservation): array
    {
        $type = $reservation->resource?->type;
        $resolved = $type !== null ? ResourceType::tryFrom($type) : null;
        $typeLabel = $resolved?->getLabel() ?? $type ?? 'منبع نامشخص';
        $resourceName = $reservation->resource?->name ?? 'منبع نامشخص';
        $isFullDay = (bool) $reservation->is_full_day;
        $start = $reservation->start_time;
        $isMultiDay = $start && $reservation->end_time && !$start->isSameDay($reservation->end_time);
        $time = $isFullDay ? 'تمام روز' : ($start ? toJalali($start, 'H:i') : '00:00');
        $startMinutes = $isFullDay ? 0 : (($start?->hour ?? 0) * 60 + ($start?->minute ?? 0));
        $duration = $isFullDay ? 0 : (int) ($start && $reservation->end_time ? $start->diffInMinutes($reservation->end_time) : 0);

        return [
            'id' => 'res-' . $reservation->id,
            'type' => 'event',
            'title' => "{$typeLabel} · {$resourceName}",
            'description' => '',
            'time' => $time,
            'start_minutes' => $startMinutes,
            'duration_minutes' => $duration,
            'is_owner' => true,
            'private' => true,
            'is_shared' => false,
            'shared_by_name' => null,
            'is_reservation_linked' => true,
            'is_full_day' => $isFullDay,
            'is_multi_day' => $isMultiDay,
            'reservation_id' => $reservation->id,
            'remind_hours' => null,
            'locked' => true,
            'mtime' => $reservation->updated_at?->toIso8601String(),
        ];
    }

    private function todayKey(): string
    {
        return once(fn () => Jalalian::now()->format('Y-m-d'));
    }

    private function range(string $navDateYmd, string $view): CalendarRange
    {
        return $this->rangeCache["$navDateYmd|$view"] ??= CalendarRange::fromNavigation($navDateYmd, $view);
    }

    private function getProfilesWithDates(): Collection
    {
        return $this->profilesWithDatesCache ??= Profile::query()
            ->select('id', 'user_id', 'birthdate', 'start_date', 'image')
            ->with('user:id,name')
            ->where(fn($q) => $q->whereNotNull('birthdate')->orWhereNotNull('start_date'))
            ->get();
    }

    private function getAllHolidays(): array
    {
        return $this->holidaysAllCache ??= HolidayService::getAll();
    }

    private function daysFor(string $navigationDate, string $view): array
    {
        try {
            return $this->range($navigationDate, $view)->days();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function groupAllDayByDate(array $allDay): array
    {
        $grouped = [];
        foreach ($allDay as $entry) {
            $grouped[$entry['date'] ?? null][] = $entry;
        }
        return $grouped;
    }

    private function withGeometry(array $pill, int $gridHeight): array
    {
        if (!isset($pill['col'], $pill['span'], $pill['start_minutes'], $pill['duration_minutes']) || $pill['span'] < 1) {
            return $pill;
        }
        $top = ($pill['start_minutes'] - self::START_HOUR * 60) * self::HOUR_HEIGHT / 60;
        $pill['top'] = $top;
        $pill['height'] = min(max($pill['duration_minutes'] * self::HOUR_HEIGHT / 60, 24), $gridHeight - $top);
        $pill['left_pct'] = $pill['col'] / $pill['span'] * 100;
        $pill['width_pct'] = 1 / $pill['span'] * 100;
        return $pill;
    }

    private function emptyDayMeta(string $navigationDate, string $todayKey): array
    {
        return [
            'jKey' => $navigationDate,
            'weekLabel' => self::WEEK_DAY_LABELS[0],
            'dayNum' => '',
            'isFriday' => false,
            'isToday' => $navigationDate === $todayKey,
            'dayAllDay' => [],
            'dayPills' => [],
            'startIso' => null,
        ];
    }

    private function buildMonthDays(int $year, int $month, string $selectedDate): array
    {
        $cacheKey = $year . '-' . $month;
        if (isset($this->monthDaysCache[$cacheKey])) {
            return $this->monthDaysCache[$cacheKey];
        }

        $days = [];

        try {
            $firstDay = new Jalalian($year, $month, 1);
            $daysInMonth = $firstDay->getMonthDays();
            $startDayOfWeek = $firstDay->getDayOfWeek();

            $startDate = $firstDay->toCarbon()->startOfDay();
            $endDate = clone $startDate;
            $endDate->addDays($daysInMonth - 1)->endOfDay();
        } catch (Throwable $e) {
            return $this->monthDaysCache[$cacheKey] = [];
        }

        $authId = $this->authId();

        $monthEvents = Event::query()
            ->with('shares:user_id,event_id')
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($q) use ($authId) {
                $q->where('user_id', $authId)
                    ->orWhere('private', false)
                    ->orWhereHas('shares', fn($sq) => $sq->where('user_id', $authId));
            })
            ->get()
            ->groupBy(fn($e) => Jalalian::fromCarbon($e->date)->format('Y-m-d'));

        $resEventIdsByDay = [];
        foreach ($monthEvents as $dayKey => $events) {
            foreach ($events as $e) {
                $rid = EventSyncService::reservationIdFrom($e->description);
                if ($rid !== null) {
                    $resEventIdsByDay[$dayKey][$rid] = true;
                }
            }
        }
        $reservationsByDay = $this->userReservationsByDay($startDate, $endDate);

        $profiles = $this->getProfilesWithDates();
        $birthdays = $profiles->pluck('birthdate')->filter()->map(fn($d) => $d->format('m-d'))->flip();
        $anniversaries = $profiles->pluck('start_date')->filter()->map(fn($d) => $d->format('m-d'))->flip();
        $allHolidays = $this->getAllHolidays();

        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $days[] = null;
        }

        $todayStr = $this->todayKey();
        $currentDate = clone $startDate;

        $now = now();
        $imminentEnd = (clone $now)->addDay();
        $isShared = fn(Event $e) => $e->user_id === $authId
            ? $e->shares->isNotEmpty()
            : $e->shares->contains('user_id', $authId);

        for ($day = 1; $day <= $daysInMonth; $day++) {
            try {
                $dateString = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $mdKey = $currentDate->format('m-d');

                $dayEvents = $monthEvents->get($dateString);
                $hasEvent = $dayEvents !== null;
                $hasBirthday = $birthdays->has($mdKey);
                $hasAnniversary = $anniversaries->has($mdKey);
                $dayHolidays = $allHolidays[$dateString] ?? [];
                $hasHoliday = $dayHolidays !== [];

                $hasShared = $hasEvent && $dayEvents->contains($isShared);
                $hasImminentShared = $hasShared && $dayEvents->contains(
                        fn(Event $e) => $isShared($e) && $e->date >= $now && $e->date <= $imminentEnd
                    );

                $eventCount = ($hasEvent ? $dayEvents->count() : 0) +
                    ($hasBirthday ? 1 : 0) +
                    ($hasAnniversary ? 1 : 0);

                $dayReservations = $reservationsByDay[$dateString] ?? [];
                $resCount = 0;
                foreach ($dayReservations as $r) {
                    if (!isset($resEventIdsByDay[$dateString][$r->id])) {
                        $resCount++;
                    }
                }
                $hasReservations = $resCount > 0;
                $eventCount += $resCount;

                $days[] = [
                    'day' => $day,
                    'date' => $dateString,
                    'isToday' => $dateString === $todayStr,
                    'isSelected' => $dateString === $selectedDate,
                    'hasEvents' => $hasEvent,
                    'hasReservations' => $hasReservations,
                    'hasBirthday' => $hasBirthday,
                    'hasAnniversary' => $hasAnniversary,
                    'eventCount' => $eventCount,
                    'hasShared' => $hasShared,
                    'hasImminentShared' => $hasImminentShared,
                    'hasHoliday' => $hasHoliday,
                    'holidayTitle' => $hasHoliday ? $dayHolidays[0]['title'] : null,
                ];

                $currentDate->addDay();
            } catch (Throwable $e) {
                $days[] = null;
            }
        }

        return $this->monthDaysCache[$cacheKey] = $days;
    }

    private function buildAllDayEntries(CalendarRange $range): array
    {
        $entries = [];
        $holidays = $this->getAllHolidays();
        $holidayIndex = 0;

        $profiles = $this->getProfilesWithDates();

        $birthdayLookup = [];
        $anniversaryLookup = [];

        foreach ($profiles as $p) {
            if ($p->birthdate) {
                $birthdayLookup[$p->birthdate->format('m-d')][] = $p;
            }
            if ($p->start_date) {
                $anniversaryLookup[$p->start_date->format('m-d')][] = $p;
            }
        }

        foreach ($range->days() as $carbon) {
            $jalaliKey = Jalalian::fromCarbon($carbon)->format('Y-m-d');
            $mdKey = $carbon->format('m-d');

            if (isset($holidays[$jalaliKey])) {
                foreach ($holidays[$jalaliKey] as $holiday) {
                    $entries[] = [
                        'id' => 'holiday-' . $jalaliKey . '-' . $holidayIndex++,
                        'type' => 'holiday',
                        'title' => $holiday['title'],
                        'description' => $holiday['type'] . (($holiday['hijri'] ?? null) ? ' · ' . $holiday['hijri'] : ''),
                        'is_owner' => false,
                        'locked' => true,
                        'date' => $jalaliKey,
                    ];
                }
            }

            foreach ($birthdayLookup[$mdKey] ?? [] as $p) {
                $entries[] = [
                    'id' => 'birthday-' . $p->id,
                    'type' => 'birthday',
                    'title' => 'تولد ' . ($p->user->name ?? 'کاربر'),
                    'description' => 'تولد مبارک!',
                    'is_owner' => false,
                    'locked' => true,
                    'date' => $jalaliKey,
                    'avatar' => $p->getImageUrl(),
                ];
            }

            foreach ($anniversaryLookup[$mdKey] ?? [] as $p) {
                $years = $p->start_date->diffInYears($carbon);
                $entries[] = [
                    'id' => 'anniversary-' . $p->id,
                    'type' => 'anniversary',
                    'title' => ($years > 0 ? $years . 'مین ' : '') . 'سالگرد همکاری ' . ($p->user->name ?? 'کاربر'),
                    'description' => 'سالگرد همکاری مبارک!',
                    'is_owner' => false,
                    'locked' => true,
                    'date' => $jalaliKey,
                    'avatar' => $p->getImageUrl(),
                ];
            }
        }

        return $entries;
    }
}
