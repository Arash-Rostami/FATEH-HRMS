<?php

namespace App\Livewire\Dashboard\Reservation;

use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Livewire\Dashboard\Reservation\Actions\BookAction;
use App\Livewire\Dashboard\Reservation\Actions\CancelAction;
use App\Livewire\Dashboard\Reservation\Presentation\AvantgardePresenter;
use App\Livewire\Dashboard\Reservation\Presentation\CalendarPresenter;
use App\Livewire\Dashboard\Reservation\Presentation\TimeSlotPresenter;
use App\Models\Reservation;
use App\Models\Resource;
use App\Services\Reservation\ValidationService;
use App\Traits\FocusOnRecord;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Lazy]
class Main extends Component
{
    use FocusOnRecord {
        FocusOnRecord::clearFocus as protected baseClearFocus;
    }

    private const HISTORY_QUERY_CAP = 500;
    private const GRID_PER_PAGE = 10;
    private const VIEWS = ['classic', 'avantgarde'];
    private const CANVAS_TABS = ['booking', 'grid'];

    private const HISTORY_TABS = [
        ['id' => 'upcoming', 'icon' => 'event_upcoming', 'label' => 'پیش‌رو'],
        ['id' => 'previous', 'icon' => 'history', 'label' => 'قبلی'],
        ['id' => 'cancelled', 'icon' => 'event_busy', 'label' => 'لغو شده'],
        ['id' => 'released', 'icon' => 'autorenew', 'label' => 'آزادشده'],
    ];

    private const HISTORY_TAB_IDS = [
        'upcoming' => true,
        'previous' => true,
        'cancelled' => true,
        'released' => true,
    ];

    public bool $deckOnly = false;

    #[Url(as: 'tab')]
    public $activeTab = 'seat';
    public $activeHistoryTab = 'upcoming';
    public bool $historyShowAll = true;
    public string $gridSearch = '';
    public array $gridPinned = [];
    public int $gridLimit = self::GRID_PER_PAGE;
    public ?int $fromYear = null;
    public ?int $fromMonth = null;
    public ?int $fromDay = null;
    public ?int $toYear = null;
    public ?int $toMonth = null;
    public ?int $toDay = null;
    #[Locked]
    public array $appliedDateParts = [];
    public $date;
    public $startTime = '09:00';
    public $endTime = '10:00';
    public array $facetFilters = [];
    public string $resourceSearch = '';
    public $zoomImageUrl = null;
    public $resourcesLimit = 6;
    public $historyLimit = 5;
    public $isRecurring = false;
    public $recurPattern = 'daily';
    public $recurCount = 4;
    public string $view = 'classic';
    public string $canvasTab = 'booking';
    public int $currentYear;
    public int $currentMonth;

    protected ValidationService $validationService;

    private ?array $bookingPermissions = null;
    private array $partialCancelCache = [];

    public function boot(ValidationService $validationService): void
    {
        $this->validationService = $validationService;
    }

    #[Computed]
    public function policies(): array
    {
        return $this->validationService->getPolicies($this->activeTab);
    }

    #[Computed]
    public function availableDates(): array
    {
        return CalendarPresenter::availableDates($this->currentYear, $this->currentMonth, $this->dateWindow, $this->allowedDays);
    }

    #[Computed]
    public function dateWindow(): ?int
    {
        $windowDays = $this->policies['window_days'] ?? null;

        return $windowDays === null ? null : (int) $windowDays;
    }

    #[Computed]
    public function allowedDays(): ?array
    {
        $days = $this->policies['allowed_days'] ?? null;

        if (! is_array($days)) {
            return null;
        }

        return array_values(array_map('strtolower', $days));
    }

    #[Computed]
    public function canPrevMonth(): bool
    {
        return CalendarPresenter::canPrevMonth($this->currentYear, $this->currentMonth);
    }

    #[Computed]
    public function canNextMonth(): bool
    {
        return CalendarPresenter::canNextMonth($this->currentYear, $this->currentMonth, $this->dateWindow);
    }

    #[Computed]
    public function currentMonthName(): string
    {
        return CalendarPresenter::currentMonthName($this->currentYear, $this->currentMonth);
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
    public function facets(): array
    {
        return Resource::metadataFacets($this->activeTab);
    }

    #[Computed]
    public function availableTimeSlots(): array
    {
        return TimeSlotPresenter::slots($this->policies['allowed_hours'] ?? null);
    }

    #[Computed]
    public function startSlotMeta(): array
    {
        $isFullDay = ResourceType::tryFrom($this->activeTab)?->isFullDay() ?? true;

        return TimeSlotPresenter::startSlotMeta($this->availableTimeSlots, $isFullDay, $this->date, $this->minNoticeHours);
    }

    #[Computed]
    public function minNoticeHours(): ?int
    {
        $hours = $this->policies['window_hours'] ?? null;

        return $hours === null ? null : (int) $hours;
    }

    #[Computed]
    public function durationBounds(): ?string
    {
        return TimeSlotPresenter::durationBounds($this->policies['min_duration_minutes'] ?? null, $this->policies['max_duration_minutes'] ?? null);
    }

    #[Computed]
    public function activeLimitUsage(): ?array
    {
        $max = $this->policies['max_per_user'] ?? null;

        if ($max === null) {
            return null;
        }

        $max = (int) $max;
        $count = $this->validationService->activeLimitCount(auth()->id(), $this->activeTab, Carbon::parse($this->date));

        return ['count' => $count, 'max' => $max, 'near' => $count >= $max];
    }

    #[Computed]
    public function cancelLimitUsage(): ?array
    {
        $limit = $this->policies['max_cancel_count'] ?? null;

        if ($limit === null) {
            return null;
        }

        $limit = max(1, (int) $limit);
        $count = $this->validationService->cancelLimitCount(auth()->id(), $this->activeTab);

        return ['count' => $count, 'max' => $limit, 'blocked' => $count >= $limit];
    }

    #[Computed]
    public function allowsRepeat(): bool
    {
        return (bool) ($this->policies['allow_repeat'] ?? true);
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
        if (ResourceType::tryFrom($this->activeTab)?->isFullDay() ?? true) {
            return null;
        }

        return TimeSlotPresenter::selectedDuration($this->date, $this->startTime, $this->endTime, $this->policies['min_duration_minutes'] ?? null, $this->policies['max_duration_minutes'] ?? null);
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

        if (ResourceType::tryFrom($this->activeTab)?->isFullDay() ?? true) {
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: 'این مورد یافت نشد.', type: 'error');
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: 'این مورد یافت نشد.', type: 'error');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    private function invalidateAfterMutation(): void
    {
        $this->invalidateResourceCache();
        unset($this->activeLimitUsage, $this->cancelLimitUsage, $this->historyReservations, $this->gridReservations, $this->totalHistoryReservations);
    }

    public function focusRecord(int $id): void
    {
        $resource = Resource::find($id);

        if (! $resource || ! $this->canBookTab($resource->type)) {
            return;
        }

        if ($resource->type !== $this->activeTab) {
            $this->activeTab = $resource->type;
        }

        $this->facetFilters = [];
        $this->resourcesLimit = max($this->resourcesLimit, 50);
    }

    public function focusDeck(int $id): void
    {
        $this->open = $id;
        $this->deckOnly = true;
    }

    public function viewInCanvas(int $reservationId): void
    {
        $resource = Reservation::forUser(auth()->id())->with('resource:id,type')->find($reservationId)?->resource;

        if (! $resource || ! $this->canBookTab($resource->type)) {
            return;
        }

        if ($resource->type !== $this->activeTab) {
            $this->activeTab = $resource->type;
        }

        $this->switchTab('booking');
        $this->focusDeck($resource->id);
    }

    public function clearFocus(): void
    {
        $this->deckOnly = false;
        $this->baseClearFocus();
    }

    public static function getHistoryTabs(): array
    {
        return self::HISTORY_TABS;
    }

    #[Computed]
    public function historyReservations()
    {
        return $this->historyGroups()->take($this->historyLimit);
    }

    #[Computed]
    public function gridReservations()
    {
        return $this->historyGroups()->take($this->gridLimit);
    }

    public function loadMoreGrid(): void
    {
        $this->gridLimit += self::GRID_PER_PAGE;
        unset($this->gridReservations);
    }

    private function historyGroups()
    {
        $showAll = $this->view === 'avantgarde' && $this->historyShowAll;
        $search = $this->view === 'avantgarde' ? $this->gridSearch : null;
        $span = $this->view === 'avantgarde' ? $this->dateSpan() : null;

        $query = Reservation::forUser(auth()->id())->with(['resource', 'cancelledBy:id,name'])
            ->forHistoryTab($this->activeHistoryTab, $showAll, $search, $span);

        if ($this->view === 'avantgarde' && $this->gridPinned !== []) {
            $ids = array_slice(array_map('intval', $this->gridPinned), 0, 50);
            $query->orderByRaw('CASE WHEN id IN ('.implode(',', $ids).') THEN 0 ELSE 1 END');
        }

        if ($showAll) {
            $query->orderByDesc('start_time');
        } else {
            match ($this->activeHistoryTab) {
                'previous' => $query->orderByDesc('start_time'),
                'cancelled' => $query->orderByDesc('cancelled_at'),
                'released' => $query->orderByDesc('start_time'),
                default => $query->orderBy('start_time'),
            };
        }

        return $query->limit(self::HISTORY_QUERY_CAP)->get()
            ->groupBy(fn(Reservation $r) => $r->parent_id ?? $r->id)
            ->map(function ($group) use ($showAll) {
                $rep = $group->first();
                $count = $group->count();
                $rep->setAttribute('series_count', $count);
                $bucket = $showAll ? AvantgardePresenter::historyBucket($rep) : $this->activeHistoryTab;
                $rep->setAttribute('history_bucket', $bucket);
                if ($bucket === 'upcoming') {
                    $rep->setAttribute('cancel_warning', $this->cancelWarningFor($rep, $count));
                }
                return $rep;
            })
            ->values();
    }

    private function cancelWarningFor(Reservation $rep, int $count): ?string
    {
        if ($count <= 1) {
            return null;
        }

        $type = $rep->resource?->type ?? '';

        $allowPartial = $this->partialCancelCache[$type]
            ??= (bool) ($this->validationService->getPolicies($type)['allow_partial_cancel'] ?? true);

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

    public function updatedGridPinned(): void
    {
        $this->gridLimit = self::GRID_PER_PAGE;
        unset($this->historyReservations, $this->gridReservations);
    }

    public function updatedGridSearch(): void
    {
        $this->historyLimit = 5;
        $this->gridLimit = self::GRID_PER_PAGE;
        unset($this->historyReservations, $this->gridReservations, $this->totalHistoryReservations);
    }

    public function applyDateSpan(): void
    {
        if (jalaliSpanFromParts($this->fromYear, $this->fromMonth, $this->fromDay, $this->toYear, $this->toMonth, $this->toDay) === null) {
            $this->dispatch('toast', message: 'بازهٔ انتخاب‌شده نامعتبر است.', type: 'warning');
            return;
        }

        $this->gridLimit = self::GRID_PER_PAGE;
        $this->appliedDateParts = $this->dateParts();
        unset($this->historyReservations, $this->gridReservations, $this->totalHistoryReservations);
    }

    public function clearDateSpan(): void
    {
        if ($this->fromYear === null && $this->fromMonth === null && $this->fromDay === null
            && $this->toYear === null && $this->toMonth === null && $this->toDay === null) {
            return;
        }

        $this->fromYear = $this->fromMonth = $this->fromDay = $this->toYear = $this->toMonth = $this->toDay = null;
        $this->gridLimit = self::GRID_PER_PAGE;
        $this->appliedDateParts = $this->dateParts();
        unset($this->historyReservations, $this->gridReservations, $this->totalHistoryReservations);
    }

    public function dateSpanActive(): bool
    {
        return $this->dateSpan() !== null;
    }

    private function dateParts(): array
    {
        return [$this->fromYear, $this->fromMonth, $this->fromDay, $this->toYear, $this->toMonth, $this->toDay];
    }

    private function dateSpan(): ?array
    {
        if (count($this->appliedDateParts) < 6) {
            return null;
        }

        return jalaliSpanFromParts(...$this->appliedDateParts);
    }

    public function showAllHistory(): void
    {
        if ($this->historyShowAll) return;

        $this->historyShowAll = true;
        $this->historyLimit = 5;
        $this->gridLimit = self::GRID_PER_PAGE;
        unset($this->historyReservations, $this->gridReservations, $this->totalHistoryReservations);
    }

    public function loadMoreResources(): void
    {
        $this->resourcesLimit += 6;
        unset($this->resources);
    }

    public function mount(): void
    {
        $view = session('reservation_view_mode', 'classic');
        $this->view = in_array($view, self::VIEWS, true) ? $view : 'classic';

        $this->ensurePermittedTab();
        $this->resetMonthCursor();
        $this->date = $this->availableDates[0]['value'] ?? now()->toDateString();
        $this->syncDefaultTimes();
    }

    public function toggleView(string $view): void
    {
        if (! in_array($view, self::VIEWS, true)) {
            return;
        }

        $this->view = $view;
        session(['reservation_view_mode' => $view]);
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

    public function placeholder(): View
    {
        return view('livewire.dashboard.reservation.placeholder')
            ->extends('layouts.app')
            ->section('content');
    }

    public function render()
    {
        $validationService = $this->validationService;

        $tabs = array_map(
            fn(array $tab): array => [...$tab, 'disabled' => !$validationService->isTypeActive($tab['id'])],
            array_filter(ResourceType::tabs(), fn(array $tab): bool => $this->userCanBook($tab['id'])),
        );
        $tabs = array_values($tabs);

        $avantgarde = [];
        if ($this->view === 'avantgarde') {
            $isFullDayTab = ResourceType::tryFrom($this->activeTab)?->isFullDay() ?? true;

            $avantgarde = [
                'canvasTabs' => AvantgardePresenter::canvasTabs(),
                'weekDayLabels' => AvantgardePresenter::weekDayLabels(),
                'activeTypeMeta' => AvantgardePresenter::activeTypeMeta($tabs, $this->activeTab),
                'isFullDayTab' => $isFullDayTab,
                'calendarCells' => AvantgardePresenter::calendarCells($this->currentYear, $this->currentMonth, $this->availableDates, $this->date, $this->allowedDays),
                'blockedDayLabels' => AvantgardePresenter::blockedDayLabels(),
                'timeRail' => $isFullDayTab ? null : AvantgardePresenter::timeRail($this->availableTimeSlots, $this->startTime, $this->endTime, $this->openResourceBusySegments, $this->date),
            ];
        }

        return view('livewire.dashboard.reservation', [
            'tabs' => $tabs,
            'historyTabs' => self::getHistoryTabs(),
            ...$avantgarde,
        ])->extends('layouts.app')->section('content');
    }

    private function canBookTab(string $type): bool
    {
        return $this->userCanBook($type) && $this->validationService->isTypeActive($type);
    }

    private function userCanBook(string $type): bool
    {
        $booking = $this->bookingPermissions ??= (auth()->user()?->booking ?? []);

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
        $this->facetFilters = [];
        $this->resourceSearch = '';
        unset($this->policies, $this->facets);
        $this->invalidateResourceCache();
        $this->resetMonthCursor();
        $this->syncDefaultTimes();
        unset($this->availableDates, $this->dateWindow, $this->canNextMonth, $this->canPrevMonth, $this->currentMonthName, $this->availableTimeSlots, $this->minNoticeHours, $this->allowedDays, $this->durationBounds, $this->activeLimitUsage, $this->cancelLimitUsage, $this->allowsRepeat, $this->recurPreview, $this->selectedDuration, $this->bookingBlockReason, $this->startSlotMeta);
    }

    private function applyFacets($query)
    {
        $paths = array_column($this->facets, 'path', 'key');

        foreach ($this->facetFilters as $key => $value) {
            if ($path = $paths[$key] ?? null) {
                $query->where("metadata->{$path}", $value);
            }
        }

        return $query;
    }

    private function resourceQuery()
    {
        [$start, $end] = $this->timeRange();
        $allowOverlap = (bool) ($this->policies['allow_overlap_release'] ?? false);

        return $this->applyFacets(Resource::available($this->activeTab, $start, $end, $allowOverlap))
            ->when($this->resourceSearch !== '', function ($q) {
                $needle = $this->resourceSearch;
                $escapedNeedle = trim(json_encode($needle), '"');

                return $q->where(function ($q2) use ($needle, $escapedNeedle) {
                    $q2->whereRaw('INSTR(name, ?) > 0', [$needle])
                        ->orWhereRaw('INSTR(CAST(metadata AS CHAR), ?) > 0', [$needle]);

                    if ($escapedNeedle !== $needle) {
                        $q2->orWhereRaw('INSTR(CAST(metadata AS CHAR), ?) > 0', [$escapedNeedle]);
                    }
                });
            })
            ->when($this->activeTab === 'meeting', fn($q) => $q->with('relatedUser.profile'));
    }

    #[Computed]
    public function resources()
    {
        return $this->resourceQuery()
            ->limit($this->resourcesLimit)->get();
    }

    #[Computed]
    public function openResourceBusySegments(): array
    {
        if (! $this->open) {
            return [];
        }

        return Reservation::where('resource_id', $this->open)
            ->whereDate('start_time', '<=', $this->date)
            ->whereDate('end_time', '>=', $this->date)
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::Released->value])
            ->get(['start_time', 'end_time'])
            ->map(fn(Reservation $r) => [
                'start' => $r->start_time->toDateString() === $this->date ? $r->start_time->format('H:i') : '00:00',
                'end' => $r->end_time->toDateString() === $this->date ? $r->end_time->format('H:i') : '23:59',
            ])
            ->all();
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

    public function setFacet(string $key, string $value): void
    {
        $this->facetFilters[$key] = ($this->facetFilters[$key] ?? null) === $value ? null : $value;
        $this->facetFilters = array_filter($this->facetFilters, fn($v) => $v !== null);
        $this->invalidateResourceCache();
    }

    public function updatedResourceSearch(): void
    {
        $this->invalidateResourceCache();
    }

    public function setStartTime($t): void
    {
        $this->startTime = $t;
        $this->invalidateResourceCache();
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, self::CANVAS_TABS, true)) {
            $this->canvasTab = $tab;
            return;
        }

        if (isset(self::HISTORY_TAB_IDS[$tab])) {
            if ($this->activeHistoryTab === $tab && ! $this->historyShowAll) return;
            $this->activeHistoryTab = $tab;
            $this->historyShowAll = false;
            $this->historyLimit = 5;
            $this->gridLimit = self::GRID_PER_PAGE;
            unset($this->historyReservations, $this->gridReservations, $this->totalHistoryReservations);
            return;
        }

        if ($this->activeTab === $tab || !$this->canBookTab($tab)) return;
        $this->activeTab = $tab;
        $this->resetFilters();
    }

    #[Computed]
    public function totalHistoryReservations()
    {
        $showAll = $this->view === 'avantgarde' && $this->historyShowAll;
        $search = $this->view === 'avantgarde' ? $this->gridSearch : null;
        $span = $this->view === 'avantgarde' ? $this->dateSpan() : null;

        return (int) Reservation::forUser(auth()->id())
            ->forHistoryTab($this->activeHistoryTab, $showAll, $search, $span)
            ->selectRaw('COUNT(DISTINCT COALESCE(parent_id, id)) as total')
            ->value('total');
    }

    #[Computed]
    public function totalResources()
    {
        return $this->resourceQuery()->count();
    }

    protected function recordFocusType(): string
    {
        return 'resource';
    }

    private function invalidateResourceCache(): void
    {
        $this->resourcesLimit = 6;
        unset($this->resources, $this->totalResources, $this->recurPreview, $this->selectedDuration, $this->bookingBlockReason, $this->startSlotMeta, $this->openResourceBusySegments);

        if ($this->open && ! $this->resources->contains('id', $this->open)) {
            $this->clearFocus();
        }
    }

    private function syncDefaultTimes(): void
    {
        $policies = $this->policies;
        [$start, $end] = TimeSlotPresenter::allowedHoursBounds($policies['allowed_hours'] ?? null);

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
