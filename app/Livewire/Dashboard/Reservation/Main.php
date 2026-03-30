<?php

namespace App\Livewire\Dashboard\Reservation;

use App\Models\Reservation;
use App\Models\Resource;
use App\Services\ReservationService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Main extends Component
{
    public $activeTab      = 'seat';
    public $activeHistoryTab = 'upcoming';
    public $date;
    public $startTime      = '09:00';
    public $endTime        = '10:00';
    public $filterFloor    = null;
    public $zoomImageUrl   = null;
    public $resourcesLimit = 6;
    public $historyLimit   = 5;

    private function timeRange(): array
    {
        $isFullDay = in_array($this->activeTab, ['seat', 'spot', 'car']);
        return [
            $isFullDay ? Carbon::parse($this->date)->startOfDay() : Carbon::parse("{$this->date} {$this->startTime}"),
            $isFullDay ? Carbon::parse($this->date)->endOfDay()   : Carbon::parse("{$this->date} {$this->endTime}"),
            $isFullDay,
        ];
    }

    private function invalidateResourceCache(): void
    {
        $this->resourcesLimit = 6;
        unset($this->resources, $this->totalResources);
    }

    #[Computed]
    public function resources()
    {
        [$start, $end] = $this->timeRange();
        return Resource::available($this->activeTab, $start, $end, $this->filterFloor)
            ->limit($this->resourcesLimit)->get();
    }

    #[Computed]
    public function totalResources()
    {
        [$start, $end] = $this->timeRange();
        return Resource::available($this->activeTab, $start, $end, $this->filterFloor)->count();
    }

    #[Computed]
    public function historyReservations()
    {
        $query = Reservation::forUser(auth()->id())->with('resource');

        match($this->activeHistoryTab) {
            'previous'  => $query->previous()->orderByDesc('start_time'),
            'cancelled' => $query->cancelled()->orderByDesc('cancelled_at'),
            default     => $query->upcoming()->orderBy('start_time'),
        };

        return $query->limit($this->historyLimit)->get();
    }

    #[Computed]
    public function totalHistoryReservations()
    {
        $query = Reservation::forUser(auth()->id());

        match($this->activeHistoryTab) {
            'previous'  => $query->previous(),
            'cancelled' => $query->cancelled(),
            default     => $query->upcoming(),
        };

        return $query->count();
    }

    #[Computed]
    public function availableDates(): array
    {
        $dates = [];
        $date  = now();
        $month = Jalalian::fromCarbon($date)->getMonth();

        for ($i = 0; $i < 21 && ($j = Jalalian::fromCarbon($date))->getMonth() === $month; $i++, $date->addDay()) {
            $dates[] = [
                'value'   => $date->toDateString(),
                'day'     => $j->format('l'),
                'date'    => $j->format('d'),
                'month'   => $j->format('F'),
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
                $f = str_replace('"', '', (string) $floor);
                $n = (int) $f;
                return [
                    'value' => $f,
                    'label' => match (true) {
                        $n < 0   => 'طبقه منفی ' . abs($n),
                        $n === 0 => 'همکف',
                        default  => 'طبقه ' . $f,
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

    public function book(int $resourceId, ReservationService $service): void
    {
        [$start, $end, $isFullDay] = $this->timeRange();
        $resource = Resource::findOrFail($resourceId);

        try {
            $service->createReservation(auth()->user(), $resource, $start, $end, $isFullDay);
            $this->dispatch('toast', message: 'رزرو با موفقیت انجام شد', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function cancel(int $reservationId, ReservationService $service): void
    {
        try {
            $service->cancelReservation(Reservation::findOrFail($reservationId), auth()->user());
            $this->dispatch('toast', message: 'رزرو با موفقیت لغو شد', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function mount(): void { $this->date = now()->toDateString(); }

    public function render()
    {
        return view('livewire.dashboard.reservation.index', ['tabs' => Resource::getTabs()])
            ->extends('layouts.app')->section('content');
    }

    public function resetFilters(): void   { $this->filterFloor = null; $this->invalidateResourceCache(); }
    public function setDate($date): void   { $this->date = $date;       $this->invalidateResourceCache(); }
    public function setStartTime($t): void { $this->startTime = $t;     $this->invalidateResourceCache(); }
    public function setEndTime($t): void   { $this->endTime = $t;       $this->invalidateResourceCache(); }

    public function setFloor($floor): void
    {
        $this->filterFloor = $this->filterFloor === $floor ? null : $floor;
        $this->invalidateResourceCache();
    }

    public function switchTab(string $tab): void
    {
        if ($this->activeTab === $tab) return;
        $this->activeTab = $tab;
        $this->resetFilters();
    }

    public function loadMoreResources(): void  { $this->resourcesLimit += 6; unset($this->resources); }
    public function switchHistoryTab(string $tab): void
    {
        if ($this->activeHistoryTab === $tab) return;
        $this->activeHistoryTab = $tab;
        $this->historyLimit = 5;
        unset($this->historyReservations, $this->totalHistoryReservations);
    }

    public function loadMoreHistory(): void
    {
        $this->historyLimit += 5;
        unset($this->historyReservations);
    }
}
