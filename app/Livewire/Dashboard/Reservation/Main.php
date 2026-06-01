<?php

namespace App\Livewire\Dashboard\Reservation;

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

class Main extends Component
{
    use FocusOnRecord;

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

    #[Computed]
    public function availableDates(): array
    {
        $dates = [];
        $date = Carbon::now();
        $month = Jalalian::fromCarbon($date)->getMonth();

        for ($i = 0; $i < 21 && ($j = Jalalian::fromCarbon($date))->getMonth() === $month; $i++, $date->addDay()) {
            $dates[] = [
                'value' => $date->toDateString(),
                'day' => $j->format('l'),
                'date' => $j->format('d'),
                'month' => $j->format('F'),
                'isToday' => $i === 0,
            ];
        }

        return $dates;
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
        $slots = [];
        for ($h = 8; $h <= 19; $h++) {
            $slots[] = sprintf('%02d:00', $h);
            $slots[] = sprintf('%02d:30', $h);
        }
        return $slots;
    }

    public function book(int $resourceId, BookAction $action): void
    {
        [$start, $end, $isFullDay] = $this->timeRange();

        $recurrence = $this->isRecurring ? ['pattern' => $this->recurPattern, 'count' => $this->recurCount] : null;

        try {
            $action->execute(auth()->user(), Resource::findOrFail($resourceId), $start, $end, $isFullDay, $recurrence);
            $this->dispatch('toast', message: 'رزرو با موفقیت انجام شد', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function cancel(int $reservationId, CancelAction $action): void
    {
        try {
            $action->execute(Reservation::findOrFail($reservationId), auth()->user());
            $this->dispatch('toast', message: 'رزرو با موفقیت لغو شد', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
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
        ];
    }

    #[Computed]
    public function historyReservations()
    {
        $query = Reservation::forUser(auth()->id())->with('resource');

        match ($this->activeHistoryTab) {
            'previous' => $query->previous()->orderByDesc('start_time'),
            'cancelled' => $query->cancelled()->orderByDesc('cancelled_at'),
            default => $query->upcoming()->orderBy('start_time'),
        };

        return $query->limit($this->historyLimit)->get();
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
        $this->date = now()->toDateString();
    }

    public function render()
    {
        return view('livewire.dashboard.reservation', [
            'tabs' => Resource::getTabs(),
            'historyTabs' => self::getHistoryTabs(),
        ])->extends('layouts.app')->section('content');
    }

    public function resetFilters(): void
    {
        $this->filterFloor = null;
        $this->invalidateResourceCache();
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

        if ($this->activeTab === $tab) return;
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
            default => $query->upcoming(),
        };

        return $query->count();
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
        unset($this->resources, $this->totalResources);
    }

    private function timeRange(): array
    {
        $isFullDay = in_array($this->activeTab, ['seat', 'spot', 'car']);
        return [
            $isFullDay ? Carbon::parse($this->date)->startOfDay() : Carbon::parse("{$this->date} {$this->startTime}"),
            $isFullDay ? Carbon::parse($this->date)->endOfDay() : Carbon::parse("{$this->date} {$this->endTime}"),
            $isFullDay,
        ];
    }
}
