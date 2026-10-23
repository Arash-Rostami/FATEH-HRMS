<?php

namespace App\Livewire\Dashboard\Reservation;

use App\Enums\ResourceType;
use App\Livewire\Dashboard\Reservation\Actions\BookAction;
use App\Livewire\Dashboard\Reservation\Actions\CancelAction;
use App\Livewire\Dashboard\Reservation\Actions\ContactColleagueAction;
use App\Livewire\Dashboard\Reservation\Actions\SearchDailyAction;
use App\Livewire\Dashboard\Reservation\Presentation\DailyPresenter;
use App\Livewire\Dashboard\Reservation\Presentation\PolicySummaryPresenter;
use App\Livewire\Dashboard\Reservation\Presentation\AvantgardePresenter;
use App\Livewire\Dashboard\Reservation\Presentation\CalendarPresenter;
use App\Livewire\Dashboard\Reservation\Presentation\TimeSlotPresenter;
use App\Models\Reservation;
use App\Models\Resource;
use App\Services\Reservation\ValidationService;
use App\Traits\FocusOnRecord;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
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

    #[Session]
    #[Url(as: 'tab')]
    public $activeTab = 'seat';
    #[Session]
    public $activeHistoryTab = 'upcoming';
    public bool $historyShowAll = true;
    public string $gridSearch = '';
    public string $gridSort = '';
    public string $gridSortDir = 'asc';
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
    #[Session]
    public string $canvasTab = 'booking';
    public int $currentYear;
    public int $currentMonth;
    public bool $dailyOpen = false;
    public array $daily = [];

    protected ValidationService $validationService;

    private ?array $bookingPermissions = null;
    private array $partialCancelCache = [];
    private array $openResources = [];

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
    public function dateWindow(): int
    {
        return min((int) ($this->policies['window_days'] ?? 20), 92);
    }

    #[Computed]
    public function allowedDays(): ?array
    {
        $days = $this->effectivePolicies()['allowed_days'] ?? null;

        if (! is_array($days)) {
            return null;
        }

        return array_values(array_map('strtolower', $days));
    }

    #[Computed]
    public function dayFreeCounts(): array
    {
        return AvantgardePresenter::dayFreeCounts($this->activeTab, (bool) ($this->policies['allow_overlap_release'] ?? false), $this->dateWindow);
    }

    #[Computed]
    public function fullyBookedDates(): array
    {
        return array_keys(array_filter($this->dayFreeCounts, fn (int $free) => $free === 0));
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
    }

    #[Computed]
    public function facets(): array
    {
        return Resource::metadataFacets($this->activeTab);
    }

    #[Computed]
    public function availableTimeSlots(): array
    {
        return TimeSlotPresenter::slots($this->policies['allowed_hours'] ?? null, $this->openResourceSlot());
    }

    private function effectivePolicies(): array
    {
        $policies = $this->policies;
        $meta = $this->openResource()?->metadata ?? [];

        if (filled($meta['available_days'] ?? null)) {
            $policies['allowed_days'] = array_values(array_intersect($policies['allowed_days'] ?? $meta['available_days'], $meta['available_days']));
        }

        if ($slot = $this->openResourceSlot()) {
            [$start, $end] = TimeSlotPresenter::allowedHoursBounds($policies['allowed_hours'] ?? null, $slot);
            $policies['allowed_hours'] = ['start' => $start->format('H:i'), 'end' => $end->format('H:i')];
        }

        return $policies;
    }

    private function openResourceSlot(): ?array
    {
        return $this->openResource()?->metadata['time_slots'] ?? null;
    }

    private function openResource(): ?Resource
    {
        if (! $this->open) {
            return null;
        }

        return $this->resources->firstWhere('id', $this->open)
            ?? ($this->openResources[$this->open] ??= Resource::where('type', $this->activeTab)->find($this->open));
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
        $max = auth()->user()->bookingMax($this->activeTab) ?? $this->policies['max_per_user'] ?? null;

        if ($max === null) {
            return null;
        }

        $max = (int) $max;
        $count = $this->validationService->activeLimitCount(auth()->id(), $this->activeTab, Carbon::parse($this->date));

        return [
            'count' => $count,
            'max' => $max,
            'near' => $count >= $max,
            'level' => PolicySummaryPresenter::quotaLevel($count, $max),
            'left' => max(0, $max - $count),
            'reset' => PolicySummaryPresenter::quotaReset(Carbon::parse($this->date)),
        ];
    }

    #[Computed]
    public function lastMinuteOpensAt(): ?string
    {
        if ($this->lastMinuteHours === null || ! ($this->activeLimitUsage['near'] ?? false)) {
            return null;
        }

        $opens = $this->timeRange()[0]->copy()->subHours($this->lastMinuteHours);

        return $opens->isFuture() ? convertToPersian(toJalali($opens, 'j F H:i')) : null;
    }

    #[Computed]
    public function policyChips(): array
    {
        return PolicySummaryPresenter::chips($this->effectivePolicies(), ResourceType::tryFrom($this->activeTab)?->isFullDay() ?? true, (string) $this->activeTab, auth()->user()->bookingMax($this->activeTab));
    }

    #[Computed]
    public function cancelLimitUsage(): ?array
    {
        $limit = $this->policies['max_cancel_count'] ?? null;

        if ($limit === null) {
            return null;
        }

        $limit = max(0, (int) $limit);
        $days = ValidationService::cancelWindowDays($this->policies);
        ['count' => $count, 'oldest' => $oldest] = $this->validationService->cancelLimitStats(auth()->id(), $this->activeTab, $this->policies);

        return [
            'count' => $count,
            'max' => $limit,
            'days' => $days,
            'blocked' => $limit >= 1 && $count >= $limit,
            'level' => PolicySummaryPresenter::quotaLevel($count, $limit),
            'left' => max(0, $limit - $count),
            'dropsInDays' => $oldest ? max(1, (int) ceil(now()->diffInDays($oldest->copy()->addDays($days)))) : null,
        ];
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
        $windowDays = $this->policies['window_days'] ?? null;
        $horizon = $windowDays !== null
            ? Carbon::now()->addDays((int) $windowDays)->endOfDay()
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
    public function lastMinuteHours(): ?int
    {
        $hours = $this->policies['last_minute_hours'] ?? null;

        return $hours === null ? null : (int) $hours;
    }

    #[Computed]
    public function bookingBlockReason(): ?string
    {
        $usage = $this->activeLimitUsage;
        if ($usage !== null && $usage['near'] && ($this->lastMinuteHours === null || $this->timeRange()[0]->gt(now()->addHours($this->lastMinuteHours)))) {
            return 'به سقف رزرو ماهانه رسیده‌اید — ابتدا یکی را لغو کنید'
                .($this->lastMinuteHours === null ? '' : ' یا رزروی را انتخاب کنید که حداکثر '.convertToPersian((string) $this->lastMinuteHours).' ساعت دیگر شروع می‌شود');
        }

        $cancel = $this->cancelLimitUsage;
        if ($cancel !== null && $cancel['blocked']) {
            return 'به سقف مجاز لغو در ' . convertToPersian((string) $cancel['days']) . ' روز اخیر رسیده‌اید — ثبت رزرو جدید موقتاً مسدود است';
        }

        [$start, $end, $fullDay] = $this->timeRange();

        if ($end->isPast() || ((! $fullDay || $this->minNoticeHours > 0) && $start->lt(now()->addHours($this->minNoticeHours ?? 0)->startOfMinute()))) {
            return 'زمان انتخابی گذشته یا کمتر از حداقل مهلت رزرو است';
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
        try {
            [$start, $end, $isFullDay] = $this->timeRange();

            $recurrence = ($this->isRecurring && $this->allowsRepeat)
                ? ['pattern' => $this->recurPattern, 'count' => $this->recurCount]
                : null;

            $action->execute(auth()->user(), Resource::where('type', $this->activeTab)->findOrFail($resourceId), $start, $end, $isFullDay, $recurrence);
            $this->dispatch('toast', message: $action->skipped > 0
                ? 'رزرو انجام شد؛ ' . convertToPersian((string) $action->skipped) . ' تکرار به‌دلیل قوانین یا تداخل ثبت نشد'
                : 'رزرو با موفقیت انجام شد', type: $action->skipped > 0 ? 'warning' : 'success');
            $this->invalidateAfterMutation();
        } catch (ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: 'این مورد یافت نشد.', type: 'error');
        } catch (Exception $e) {
            report($e);
            $this->dispatch('toast', message: $this->domainMessage($e), type: 'error');
        }
    }

    private function domainMessage(Exception $e): string
    {
        return $e instanceof QueryException ? 'خطایی رخ داد؛ لطفاً دوباره تلاش کنید.' : $e->getMessage();
    }

    public function cancel(int $reservationId, CancelAction $action): void
    {
        try {
            $action->execute(Reservation::findOrFail($reservationId), auth()->user());
            $this->dispatch('toast', message: 'رزرو با موفقیت لغو شد', type: 'success');
            $this->invalidateAfterMutation();
        } catch (ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: 'این مورد یافت نشد.', type: 'error');
        } catch (Exception $e) {
            report($e);
            $this->dispatch('toast', message: $this->domainMessage($e), type: 'error');
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
            $this->resetFilters();
        }

        $this->facetFilters = [];
        $this->focusDeck($id);
        $this->deckOnly = false;
    }

    public function focusDeck(int $id): void
    {
        $this->resourcesLimit = max($this->resourcesLimit, 50);
        $this->open = $id;
        $this->deckOnly = true;
        unset($this->resources, $this->totalResources, $this->availableTimeSlots, $this->startSlotMeta, $this->allowedDays, $this->availableDates, $this->policyChips);

        if (! in_array($this->date, array_column($this->availableDates, 'value'), true)) {
            $this->date = $this->availableDates[0]['value'] ?? $this->date;
        }

        if (! in_array($this->startTime, $this->availableTimeSlots, true) || ! in_array($this->endTime, $this->availableTimeSlots, true)) {
            $this->syncDefaultTimes();
        }

        unset($this->resources, $this->totalResources, $this->openResourceBusySegments, $this->bookingBlockReason, $this->selectedDuration);
    }

    public function viewInCanvas(int $reservationId): void
    {
        $resource = Reservation::forUser(auth()->id())->with('resource:id,type')->find($reservationId)?->resource;

        if (! $resource || ! $this->canBookTab($resource->type)) {
            return;
        }

        if ($resource->type !== $this->activeTab) {
            $this->activeTab = $resource->type;
            $this->resetFilters();
        }

        $this->switchTab('booking');
        $this->focusDeck($resource->id);
    }

    public function clearFocus(): void
    {
        $this->deckOnly = false;
        $wasOpen = $this->open !== null;
        $this->baseClearFocus();

        if ($wasOpen) {
            $this->resourcesLimit = 6;
            unset($this->resources, $this->totalResources, $this->allowedDays, $this->availableDates, $this->availableTimeSlots, $this->policyChips);
        }
    }

    public function updated($name, $value): void
    {
        if ($name === 'activeTab') {
            if (! is_string($value)) {
                $this->activeTab = 'seat';
            }

            $this->ensurePermittedTab();
            $this->resetFilters();
        }

        if ($name === 'date' && ! (is_string($value) && preg_match('/^(20\d{2})-(\d{2})-(\d{2})$/', $value, $part) && checkdate((int) $part[2], (int) $part[3], (int) $part[1]))) {
            $this->date = now()->toDateString();
        }

        if (in_array($name, ['startTime', 'endTime'], true) && ! (is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value))) {
            $this->{$name} = $name === 'startTime' ? '09:00' : '10:00';
        }
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

    public function sortBy(string $column): void
    {
        if (!in_array($column, ['time', 'resource'], true)) {
            return;
        }

        if ($this->gridSort === $column) {
            $this->gridSortDir = $this->gridSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->gridSort = $column;
            $this->gridSortDir = 'asc';
        }

        $this->gridLimit = self::GRID_PER_PAGE;
        unset($this->gridReservations);
    }

    public function resetSort(): void
    {
        $this->gridSort = '';
        $this->gridSortDir = 'asc';
        $this->gridLimit = self::GRID_PER_PAGE;
        unset($this->gridReservations);
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

        $query = Reservation::forUser(auth()->id())->with(['resource.relatedUser.profile', 'cancelledBy:id,name'])
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

        $groups = $query->limit(self::HISTORY_QUERY_CAP)->get()
            ->groupBy(fn(Reservation $r) => $r->parent_id ?? $r->id)
            ->map(function ($group) use ($showAll) {
                $rep = $group->first(fn(Reservation $r) => $r->isCancellable()) ?? $group->first();
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

        if ($this->view !== 'avantgarde' || $this->gridSort === '') {
            return $groups;
        }

        $pinned = array_flip(array_map('intval', array_slice($this->gridPinned, 0, 50)));
        $key = $this->gridSort === 'resource'
            ? fn(Reservation $r) => mb_strtolower((string) ($r->resource->name ?? ''))
            : fn(Reservation $r) => $r->start_time;

        $dir = $this->gridSortDir === 'desc' ? -1 : 1;

        return $groups->sort(
            fn(Reservation $a, Reservation $b) => (isset($pinned[$b->id]) <=> isset($pinned[$a->id])) ?: (($key($a) <=> $key($b)) * $dir)
        )->values();
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

        if (!isset(self::HISTORY_TAB_IDS[$this->activeHistoryTab])) {
            $this->activeHistoryTab = 'upcoming';
        }
        if (!in_array($this->canvasTab, self::CANVAS_TABS, true)) {
            $this->canvasTab = 'booking';
        }

        $this->ensurePermittedTab();
        $this->resetMonthCursor();
        $this->date = $this->availableDates[0]['value'] ?? now()->toDateString();
        $this->syncDefaultTimes();
        $this->daily = DailyPresenter::defaults($this->date, $this->activeTab);
    }

    public function openDaily(): void
    {
        $this->resetErrorBag();
        $this->daily = DailyPresenter::defaults($this->date, $this->activeTab);
        $this->dailyOpen = true;
    }

    public function updatedDaily($value, ?string $key = null): void
    {
        if (in_array($key, ['type', 'search', 'mine'], true)) {
            $this->daily['limit'] = DailyPresenter::PER_PAGE;
        }
    }

    public function setDailyQuick(string $key): void
    {
        $this->resetErrorBag();
        $this->daily = DailyPresenter::withQuick($this->daily, $key);
    }

    public function shiftDaily(int $direction): void
    {
        $this->daily = DailyPresenter::withShift($this->daily, $direction);
    }

    public function applyDailyRange(): void
    {
        $this->resetErrorBag();

        if ($problem = DailyPresenter::rangeProblem($this->daily)) {
            $this->addError($problem[0], $problem[1]);
            return;
        }

        $this->daily = DailyPresenter::withCustomRange($this->daily) ?? $this->daily;
    }

    public function clearDailyRange(): void
    {
        $this->resetErrorBag();
        $this->daily = DailyPresenter::withoutCustomRange($this->daily);
    }

    public function loadMoreDaily(): void
    {
        $this->daily['limit'] = (int) ($this->daily['limit'] ?? DailyPresenter::PER_PAGE) + DailyPresenter::PER_PAGE;
    }

    public function reachOut(int $reservationId, string $preset, ContactColleagueAction $action): void
    {
        try {
            $reservation = Reservation::with('resource')->findOrFail($reservationId);

            if (! $this->userCanBook((string) $reservation->resource?->type)) {
                throw new Exception('اجازهٔ این کار را ندارید.');
            }

            $action->execute($reservation, auth()->user(), $preset);
            $this->dispatch('toast', message: 'پیام برای همکار ارسال شد', type: 'success');
        } catch (ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: 'این مورد یافت نشد.', type: 'error');
        } catch (Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    #[Computed]
    public function bookableTabs(): array
    {
        return array_values(array_filter(ResourceType::tabs(), fn(array $tab): bool => $this->userCanBook($tab['id'])));
    }

    #[Computed]
    public function dailyData(): array
    {
        return $this->dailyOpen
            ? app(SearchDailyAction::class)->execute($this->daily, array_column($this->bookableTabs, 'id'), (int) auth()->id())
            : ['groups' => [], 'total' => 0, 'shown' => 0, 'resources' => 0, 'more' => false, 'capped' => false, 'label' => '', 'presets' => []];
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
        $this->resetMonthCursor();
        unset($this->canNextMonth, $this->canPrevMonth, $this->currentMonthName);
        $this->setDate(now()->toDateString());
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
            $this->bookableTabs,
        );

        $avantgarde = [];
        if ($this->view === 'avantgarde') {
            $isFullDayTab = ResourceType::tryFrom($this->activeTab)?->isFullDay() ?? true;

            $avantgarde = [
                'canvasTabs' => AvantgardePresenter::canvasTabs(),
                'weekDayLabels' => AvantgardePresenter::weekDayLabels(),
                'activeTypeMeta' => AvantgardePresenter::activeTypeMeta($tabs, $this->activeTab),
                'isFullDayTab' => $isFullDayTab,
                'calendarCells' => AvantgardePresenter::calendarCells($this->currentYear, $this->currentMonth, $this->availableDates, $this->date, $this->allowedDays, $this->fullyBookedDates),
                'blockedDayLabels' => AvantgardePresenter::blockedDayLabels(),
                'timeRail' => $isFullDayTab ? null : AvantgardePresenter::timeRail($this->availableTimeSlots, $this->startTime, $this->endTime, $this->openResourceBusySegments, $this->date),
            ];
        }

        return view('livewire.dashboard.reservation', [
            'tabs' => $tabs,
            'historyTabs' => self::getHistoryTabs(),
            'dailyQuickTabs' => $this->dailyOpen ? DailyPresenter::quickTabs() : [],
            'dailyMonthOptions' => $this->dailyOpen ? DailyPresenter::monthOptions() : [],
            'dailyYearOptions' => $this->dailyOpen ? DailyPresenter::yearOptions() : [],
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
        if (! is_string($this->activeTab) || ! ResourceType::tryFrom($this->activeTab)) {
            $this->activeTab = 'seat';
        }

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
        unset($this->availableDates, $this->dayFreeCounts, $this->fullyBookedDates, $this->dateWindow, $this->canNextMonth, $this->canPrevMonth, $this->currentMonthName, $this->availableTimeSlots, $this->minNoticeHours, $this->lastMinuteHours, $this->lastMinuteOpensAt, $this->policyChips, $this->allowedDays, $this->durationBounds, $this->activeLimitUsage, $this->cancelLimitUsage, $this->allowsRepeat, $this->recurPreview, $this->selectedDuration, $this->bookingBlockReason, $this->startSlotMeta);

        if (! in_array($this->date, array_column($this->availableDates, 'value'), true)) {
            $this->date = $this->availableDates[0]['value'] ?? $this->date;
        }

        unset($this->resources, $this->totalResources);
        $this->syncDefaultTimes();
        unset($this->resources, $this->totalResources, $this->availableTimeSlots, $this->startSlotMeta);
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
            ->when($this->resourceSearch !== '', fn($q) => $q->matching($this->resourceSearch))
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
            ->whereHas('resource', fn ($q) => $q->where('type', $this->activeTab))
            ->whereDate('start_time', '<=', $this->date)
            ->whereDate('end_time', '>=', $this->date)
            ->held()
            ->get(['start_time', 'end_time'])
            ->map(fn(Reservation $r) => [
                'start' => $r->start_time->toDateString() === $this->date ? $r->start_time->format('H:i') : '00:00',
                'end' => $r->end_time->toDateString() === $this->date ? $r->end_time->format('H:i') : '23:59',
            ])
            ->all();
    }

    public function setDate($date): void
    {
        if (! in_array($date, array_column($this->availableDates, 'value'), true)) {
            return;
        }

        $this->date = $date;
        $this->invalidateResourceCache();
        unset($this->activeLimitUsage);
    }

    public function setEndTime($t): void
    {
        if (! in_array($t, $this->availableTimeSlots, true)) {
            return;
        }

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
        if (! in_array($t, $this->availableTimeSlots, true)) {
            return;
        }

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
        $this->resourcesLimit = $this->open ? 50 : 6;
        unset($this->availableTimeSlots, $this->resources, $this->totalResources, $this->recurPreview, $this->selectedDuration, $this->bookingBlockReason, $this->startSlotMeta, $this->openResourceBusySegments, $this->lastMinuteOpensAt);

        if ($this->open && ! $this->resources->contains('id', $this->open)) {
            $this->clearFocus();
        }
    }

    private function syncDefaultTimes(): void
    {
        $policies = $this->policies;
        [$start, $end] = TimeSlotPresenter::allowedHoursBounds($policies['allowed_hours'] ?? null, $this->openResourceSlot());

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
