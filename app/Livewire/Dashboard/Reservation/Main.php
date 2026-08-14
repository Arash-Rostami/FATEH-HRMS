<?php

namespace App\Livewire\Dashboard\Reservation;

use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Livewire\Dashboard\Reservation\Actions\BookAction;
use App\Livewire\Dashboard\Reservation\Actions\CancelAction;
use App\Models\Reservation;
use App\Models\Resource;
use App\Services\Reservation\ValidationService;
use App\Traits\FocusOnRecord;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Morilog\Jalali\Jalalian;
use Throwable;

class Main extends Component
{
    use FocusOnRecord;

    private const HISTORY_QUERY_CAP = 500;

    #[Url(as: 'tab')]
    public $activeTab = 'seat';
    public $activeHistoryTab = 'upcoming';
    public $date;
    public $startTime = '09:00';
    public $endTime = '10:00';
    public $filterFloor = null;
    public $zoomImageUrl = null;
    public $resourcesLimit = 6;
    public $historyLimit = 5;
    public $isRecurring = false;
    public $recurPattern = 'daily';
    public $recurCount = 4;
    public int $currentYear;
    public int $currentMonth;

    #[Computed]
    public function availableDates(): array
    {
        $dates = [];
        $now = Carbon::now()->startOfDay();
        $windowDays = $this->dateWindow;
        $allowedDays = $this->allowedDays;

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

        $horizon = Carbon::now()->addDays((int) $windowDays)->endOfDay();

        try {
            $daysInMonth = (new Jalalian($this->currentYear, $this->currentMonth, 1))->getMonthDays();
            $date = (new Jalalian($this->currentYear, $this->currentMonth, 1))->toCarbon()->startOfDay();
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

    #[Computed]
    public function dateWindow(): ?int
    {
        $windowDays = app(ValidationService::class)
            ->getPolicies($this->activeTab)['window_days'] ?? null;

        return $windowDays === null ? null : (int) $windowDays;
    }

    #[Computed]
    public function allowedDays(): ?array
    {
        $days = app(ValidationService::class)
            ->getPolicies($this->activeTab)['allowed_days'] ?? null;

        if (! is_array($days)) {
            return null;
        }

        return array_values(array_map('strtolower', $days));
    }

    #[Computed]
    public function canPrevMonth(): bool
    {
        $now = Jalalian::now();

        if ($this->currentYear !== $now->getYear()) {
            return $this->currentYear > $now->getYear();
        }

        return $this->currentMonth > $now->getMonth();
    }

    #[Computed]
    public function canNextMonth(): bool
    {
        if ($this->dateWindow === null) {
            return false;
        }

        $horizon = Carbon::now()->addDays((int) $this->dateWindow)->startOfDay();
        $year = $this->currentYear;
        $month = $this->currentMonth + 1;

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

    #[Computed]
    public function currentMonthName(): string
    {
        try {
            return (new Jalalian($this->currentYear, $this->currentMonth, 1))->format('F Y');
        } catch (Throwable $e) {
            return '';
        }
    }

    public function nextMonth(): void
    {
        if (! $this->canNextMonth) {
            return;
        }

        if (++$this->currentMonth > 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        }

        unset($this->availableDates);
    }

    public function prevMonth(): void
    {
        if (! $this->canPrevMonth) {
            return;
        }

        if (--$this->currentMonth < 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        }

        unset($this->availableDates);
    }

    #[Computed]
    public function availableFloors()
    {
        return Resource::where('type', $this->activeTab)->where('status', 'active')
            ->get()->pluck('metadata.floor')->filter()->unique()->sort()
            ->map(function ($floor) {
                $f = str_replace('"', '', (string)$floor);
                $n = (int)$f;
                return [
                    'value' => $f,
                    'label' => match (true) {
                        $n < 0 => 'طبقه منفی ' . abs($n),
                        $n === 0 => 'همکف',
                        default => 'طبقه ' . $f,
                    },
                ];
            })->values()->toArray();
    }

    #[Computed]
    public function availableTimeSlots(): array
    {
        $allowed = app(ValidationService::class)
            ->getPolicies($this->activeTab)['allowed_hours'] ?? null;

        [$start, $end] = $this->allowedHoursBounds($allowed);

        $slots = [];
        for ($cursor = $start->copy(); $cursor < $end; $cursor->addMinutes(30)) {
            $slots[] = $cursor->format('H:i');
        }

        return $slots;
    }

    private function allowedHoursBounds(?array $allowed): array
    {
        $start = Carbon::parse($allowed['start'] ?? '08:00');
        $end = Carbon::parse($allowed['end'] ?? '20:00');

        if ($end <= $start) {
            $start = Carbon::parse('08:00');
            $end = Carbon::parse('20:00');
        }

        return [$start, $end];
    }

    #[Computed]
    public function startSlotMeta(): array
    {
        $slots = $this->availableTimeSlots;

        if ($this->activeTab !== ResourceType::Meeting->value || empty($slots)) {
            return ['states' => [], 'first' => null];
        }

        $today = Carbon::parse($this->date)->startOfDay();

        if (! $today->isSameDay(Carbon::now()->startOfDay())) {
            return ['states' => array_fill_keys($slots, 'ok'), 'first' => $slots[0]];
        }

        $now = Carbon::now();
        $cutoff = $now->copy()->addHours((int) ($this->minNoticeHours ?? 0));
        $states = [];
        $first = null;

        foreach ($slots as $t) {
            $slot = Carbon::parse("{$this->date} {$t}");

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

    #[Computed]
    public function minNoticeHours(): ?int
    {
        $hours = app(ValidationService::class)
            ->getPolicies($this->activeTab)['window_hours'] ?? null;

        return $hours === null ? null : (int) $hours;
    }

    #[Computed]
    public function durationBounds(): ?string
    {
        $policies = app(ValidationService::class)->getPolicies($this->activeTab);
        $min = $policies['min_duration_minutes'] ?? null;
        $max = $policies['max_duration_minutes'] ?? null;
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

    #[Computed]
    public function activeLimitUsage(): ?array
    {
        $max = app(ValidationService::class)
            ->getPolicies($this->activeTab)['max_per_user'] ?? null;

        if ($max === null) {
            return null;
        }

        $max = (int) $max;
        $date = Carbon::parse($this->date);

        $count = Reservation::forUser(auth()->id())
            ->whereHas('resource', fn($q) => $q->where('type', $this->activeTab))
            ->whereMonth('start_time', $date->month)
            ->whereYear('start_time', $date->year)
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::Released->value])
            ->toBase()->count();

        return ['count' => $count, 'max' => $max, 'near' => $count >= $max];
    }

    #[Computed]
    public function cancelLimitUsage(): ?array
    {
        $limit = app(ValidationService::class)
            ->getPolicies($this->activeTab)['max_cancel_count'] ?? null;

        if ($limit === null) {
            return null;
        }

        $limit = max(1, (int) $limit);

        $count = Reservation::forUser(auth()->id())
            ->whereHas('resource', fn($q) => $q->where('type', $this->activeTab))
            ->where('status', ReservationStatus::CancelledUser->value)
            ->where(fn($q) => $q->whereNull('cancelled_at')->orWhere('cancelled_at', '>=', now()->subDays(30)))
            ->toBase()->count();

        return ['count' => $count, 'max' => $limit, 'blocked' => $count >= $limit];
    }

    #[Computed]
    public function allowsRepeat(): bool
    {
        return (bool) (app(ValidationService::class)
            ->getPolicies($this->activeTab)['allow_repeat'] ?? true);
    }

    #[Computed]
    public function recurPreview(): array
    {
        if (! $this->isRecurring || ! $this->allowsRepeat) {
            return [];
        }

        $intervalDays = $this->recurPattern === 'weekly' ? 7 : 1;
        $count = max(2, min(52, (int) $this->recurCount));

        $start = Carbon::parse($this->date);
        $now = Carbon::now()->startOfDay();
        $horizon = $this->dateWindow !== null
            ? Carbon::now()->addDays((int) $this->dateWindow)->endOfDay()
            : null;
        $allowed = $this->allowedDays;

        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $day = $start->copy()->addDays($i * $intervalDays);
            $ok = $day >= $now
                && ($horizon === null || $day <= $horizon)
                && ($allowed === null || in_array(strtolower($day->englishDayOfWeek), $allowed, true));
            $items[] = [
                'date' => toJalali($day, 'j F'),
                'ok' => $ok,
            ];
        }

        return $items;
    }

    #[Computed]
    public function selectedDuration(): ?array
    {
        if ($this->activeTab !== ResourceType::Meeting->value) {
            return null;
        }

        $start = Carbon::parse("{$this->date} {$this->startTime}");
        $end = Carbon::parse("{$this->date} {$this->endTime}");
        $minutes = (int) $start->diffInMinutes($end);

        $policies = app(ValidationService::class)->getPolicies($this->activeTab);
        $min = $policies['min_duration_minutes'] ?? null;
        $max = $policies['max_duration_minutes'] ?? null;

        $valid = $minutes > 0
            && ($min === null || $minutes >= (int) $min)
            && ($max === null || $minutes <= (int) $max);

        if ($minutes <= 0) {
            $text = 'زمان پایان باید بعد از شروع باشد';
        } else {
            $text = 'مدت انتخابی: ' . $this->humanizeMinutes($minutes);
        }

        return ['minutes' => $minutes, 'text' => $text, 'valid' => $valid];
    }

    #[Computed]
    public function bookingBlockReason(): ?string
    {
        $usage = $this->activeLimitUsage;
        if ($usage !== null && $usage['near']) {
            return 'به سقف رزرو ماهانه رسیده‌اید — ابتدا یکی را لغو کنید';
        }

        $cancel = $this->cancelLimitUsage;
        if ($cancel !== null && $cancel['blocked']) {
            return 'به سقف لغو ماهانه رسیده‌اید — ثبت رزرو جدید موقتاً مسدود است';
        }

        if ($this->activeTab !== ResourceType::Meeting->value) {
            return null;
        }

        $duration = $this->selectedDuration;
        if ($duration === null) {
            return null;
        }

        if ($duration['minutes'] <= 0) {
            return 'زمان پایان باید بعد از شروع باشد';
        }

        if (! $duration['valid']) {
            $bounds = $this->durationBounds;
            return $bounds !== null ? $bounds : 'مدت رزرو خارج از بازه مجاز است';
        }

        return null;
    }

    private function humanizeMinutes(int $minutes): string
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

    public function book(int $resourceId, BookAction $action): void
    {
        [$start, $end, $isFullDay] = $this->timeRange();

        $recurrence = ($this->isRecurring && $this->allowsRepeat)
            ? ['pattern' => $this->recurPattern, 'count' => $this->recurCount]
            : null;

        try {
            $action->execute(auth()->user(), Resource::findOrFail($resourceId), $start, $end, $isFullDay, $recurrence);
            $this->dispatch('toast', message: 'رزرو با موفقیت انجام شد', type: 'success');
            $this->invalidateAfterMutation();
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function cancel(int $reservationId, CancelAction $action): void
    {
        try {
            $action->execute(Reservation::findOrFail($reservationId), auth()->user());
            $this->dispatch('toast', message: 'رزرو با موفقیت لغو شد', type: 'success');
            $this->invalidateAfterMutation();
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    private function invalidateAfterMutation(): void
    {
        $this->invalidateResourceCache();
        unset($this->activeLimitUsage, $this->cancelLimitUsage, $this->historyReservations, $this->totalHistoryReservations);
    }

    /**
     * FOCUS: a resource is listed only inside its own type tab. If we don't switch
     * `activeTab` to the resource's `type`, a non-"seat" resource is filtered out of
     * resources() entirely and the record-focus dispatch has nothing to scroll to.
     * So jump to the right tab, drop the floor filter, and widen the page size; the
     * existing `->when($this->open, orderByRaw ...)` in resources() then floats it to
     * the top where it gets scrolled to and flashed.
     */
    public function focusRecord(int $id): void
    {
        $resource = Resource::find($id);

        if (! $resource) {
            return;
        }

        if ($resource->type !== $this->activeTab) {
            $this->activeTab = $resource->type;
        }

        $this->filterFloor = null;
        $this->resourcesLimit = max($this->resourcesLimit, 50);
    }

    public static function getHistoryTabs(): array
    {
        return [
            ['id' => 'upcoming', 'icon' => 'event_upcoming', 'label' => 'پیش‌رو'],
            ['id' => 'previous', 'icon' => 'history', 'label' => 'قبلی'],
            ['id' => 'cancelled', 'icon' => 'event_busy', 'label' => 'لغو شده'],
            ['id' => 'released', 'icon' => 'autorenew', 'label' => 'آزادشده'],
        ];
    }

    #[Computed]
    public function historyReservations()
    {
        $query = Reservation::forUser(auth()->id())->with('resource');

        match ($this->activeHistoryTab) {
            'previous' => $query->previous()->orderByDesc('start_time'),
            'cancelled' => $query->cancelled()->orderByDesc('cancelled_at'),
            'released' => $query->released()->orderByDesc('start_time'),
            default => $query->upcoming()->orderBy('start_time'),
        };

        return $query->limit(self::HISTORY_QUERY_CAP)->get()
            ->groupBy(fn(Reservation $r) => $r->parent_id ?? $r->id)
            ->map(function ($group) {
                $rep = $group->first();
                $count = $group->count();
                $rep->setAttribute('series_count', $count);
                if ($this->activeHistoryTab === 'upcoming') {
                    $rep->setAttribute('cancel_warning', $this->cancelWarningFor($rep, $count));
                }
                return $rep;
            })
            ->values()
            ->take($this->historyLimit);
    }

    private function cancelWarningFor(Reservation $rep, int $count): ?string
    {
        if ($count <= 1) {
            return null;
        }

        $allowPartial = (bool)(app(ValidationService::class)
            ->getPolicies($rep->resource?->type ?? '')['allow_partial_cancel'] ?? true);

        if ($allowPartial) {
            return null;
        }

        return 'هشدار: لغو این رزرو، تمام رزروهای این سری تکرارشونده را لغو می‌کند';
    }

    public function loadMoreHistory(): void
    {
        $this->historyLimit += 5;
        unset($this->historyReservations);
    }

    public function loadMoreResources(): void
    {
        $this->resourcesLimit += 6;
        unset($this->resources);
    }

    public function mount(): void
    {
        $this->ensurePermittedTab();
        $this->resetMonthCursor();
        $this->date = $this->availableDates[0]['value'] ?? now()->toDateString();
        $this->syncDefaultTimes();
    }

    public function goToday(): void
    {
        $this->setDate(now()->toDateString());
        $this->resetMonthCursor();
        unset($this->availableDates, $this->canNextMonth, $this->canPrevMonth, $this->currentMonthName);
        $this->dispatch('scroll-to-selected');
    }

    private function resetMonthCursor(): void
    {
        $now = Jalalian::now();
        $this->currentYear = $now->getYear();
        $this->currentMonth = $now->getMonth();
    }

    public function render()
    {
        $tabs = array_map(
            fn(array $tab): array => [...$tab, 'disabled' => !app(ValidationService::class)->isTypeActive($tab['id'])],
            array_filter(ResourceType::tabs(), fn(array $tab): bool => $this->userCanBook($tab['id'])),
        );

        return view('livewire.dashboard.reservation', [
            'tabs' => array_values($tabs),
            'historyTabs' => self::getHistoryTabs(),
        ])->extends('layouts.app')->section('content');
    }

    private function canBookTab(string $type): bool
    {
        return $this->userCanBook($type) && app(ValidationService::class)->isTypeActive($type);
    }

    private function userCanBook(string $type): bool
    {
        $booking = auth()->user()?->booking ?? [];

        return ($booking['all'] ?? false) === true || ($booking[$type] ?? false) === true;
    }

    private function ensurePermittedTab(): void
    {
        if ($this->canBookTab($this->activeTab)) {
            return;
        }

        $first = collect(ResourceType::tabs())->first(fn (array $tab) => $this->canBookTab($tab['id']));

        if ($first) {
            $this->activeTab = $first['id'];
        }
    }

    public function resetFilters(): void
    {
        $this->filterFloor = null;
        $this->invalidateResourceCache();
        $this->resetMonthCursor();
        $this->syncDefaultTimes();
        unset($this->availableDates, $this->dateWindow, $this->canNextMonth, $this->canPrevMonth, $this->currentMonthName, $this->availableTimeSlots, $this->minNoticeHours, $this->allowedDays, $this->durationBounds, $this->activeLimitUsage, $this->cancelLimitUsage, $this->allowsRepeat, $this->recurPreview, $this->selectedDuration, $this->bookingBlockReason, $this->startSlotMeta);
    }

    #[Computed]
    public function resources()
    {
        [$start, $end] = $this->timeRange();
        $allowOverlap = (bool)(app(ValidationService::class)
            ->getPolicies($this->activeTab)['allow_overlap_release'] ?? false);

        return Resource::available($this->activeTab, $start, $end, $this->filterFloor, $allowOverlap)
            ->when($this->open, fn($q) => $q->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$this->open]))
            ->limit($this->resourcesLimit)->get();
    }

    public function setDate($date): void
    {
        $this->date = $date;
        $this->invalidateResourceCache();
        unset($this->activeLimitUsage);
    }

    public function setEndTime($t): void
    {
        $this->endTime = $t;
        $this->invalidateResourceCache();
    }

    public function setFloor($floor): void
    {
        $this->filterFloor = $this->filterFloor === $floor ? null : $floor;
        $this->invalidateResourceCache();
    }

    public function setStartTime($t): void
    {
        $this->startTime = $t;
        $this->invalidateResourceCache();
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, array_column(self::getHistoryTabs(), 'id'))) {
            if ($this->activeHistoryTab === $tab) return;
            $this->activeHistoryTab = $tab;
            $this->historyLimit = 5;
            unset($this->historyReservations, $this->totalHistoryReservations);
            return;
        }

        if ($this->activeTab === $tab || !$this->canBookTab($tab)) return;
        $this->activeTab = $tab;
        $this->resetFilters();
    }

    #[Computed]
    public function totalHistoryReservations()
    {
        $query = Reservation::forUser(auth()->id());

        match ($this->activeHistoryTab) {
            'previous' => $query->previous(),
            'cancelled' => $query->cancelled(),
            'released' => $query->released(),
            default => $query->upcoming(),
        };

        return (int) $query
            ->selectRaw('COUNT(DISTINCT COALESCE(parent_id, id)) as total')
            ->value('total');
    }

    #[Computed]
    public function totalResources()
    {
        [$start, $end] = $this->timeRange();

        $allowOverlap = (bool)(app(ValidationService::class)
            ->getPolicies($this->activeTab)['allow_overlap_release'] ?? false);

        return Resource::available($this->activeTab, $start, $end, $this->filterFloor, $allowOverlap)
            ->count();
    }

    protected function recordFocusType(): string
    {
        return 'resource';
    }

    private function invalidateResourceCache(): void
    {
        $this->resourcesLimit = 6;
        unset($this->resources, $this->totalResources, $this->recurPreview, $this->selectedDuration, $this->bookingBlockReason, $this->startSlotMeta);
    }

    private function syncDefaultTimes(): void
    {
        $policies = app(ValidationService::class)->getPolicies($this->activeTab);
        [$start, $end] = $this->allowedHoursBounds($policies['allowed_hours'] ?? null);

        $today = Carbon::parse($this->date)->startOfDay();
        $now = Carbon::now();

        if ($today->isSameDay($now)) {
            $minNotice = $policies['window_hours'] ?? null;
            $earliest = $minNotice !== null ? $now->copy()->addHours((int) $minNotice) : $now->copy();
        } else {
            $earliest = $today->copy();
        }

        $dayStart = $today->copy()->setTime($start->hour, $start->minute);
        $dayEnd = $today->copy()->setTime($end->hour, $end->minute);

        if ($earliest >= $dayEnd) {
            return;
        }

        $slot = $dayStart->copy();
        while ($slot < $earliest) {
            $slot->addMinutes(30);
        }

        if ($slot >= $dayEnd) {
            return;
        }

        $this->startTime = $slot->format('H:i');

        $endSlot = $slot->copy()->addMinutes(30);
        if ($endSlot > $dayEnd) {
            $endSlot = $dayEnd->copy();
        }

        $this->endTime = $endSlot->format('H:i');
    }

    private function timeRange(): array
    {
        $isFullDay = ResourceType::tryFrom($this->activeTab)?->isFullDay() ?? false;
        return [
            $isFullDay ? Carbon::parse($this->date)->startOfDay() : Carbon::parse("{$this->date} {$this->startTime}"),
            $isFullDay ? Carbon::parse($this->date)->endOfDay() : Carbon::parse("{$this->date} {$this->endTime}"),
            $isFullDay,
        ];
    }
}
