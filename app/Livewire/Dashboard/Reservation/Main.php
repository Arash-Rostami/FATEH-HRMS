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
    public $activeTab = 'seat';
    public $date;
    public $startTime = '09:00';
    public $endTime = '10:00';
    public $filterFloor = null;
    public $zoomImageUrl = null;
    public $resourcesLimit = 6;
    public $upcomingLimit = 5;
    public $previousLimit = 5;
    public $cancelledLimit = 5;

    #[Computed]
    public function availableDates(): array
    {
        $dates = [];
        $date = now();
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
        return Resource::query()
            ->where('type', $this->activeTab)
            ->where('status', 'active')
            ->get()
            ->pluck('metadata.floor')
            ->filter()
            ->unique()
            ->sort()
            ->map(function ($floor) {
                $cleanFloor = str_replace('"', '', (string)$floor);
                $floorNum = (int)$cleanFloor;

                return [
                    'value' => $cleanFloor,
                    'label' => match (true) {
                        $floorNum < 0 => 'طبقه منفی ' . abs($floorNum),
                        $floorNum === 0 => 'همکف',
                        default => 'طبقه ' . $cleanFloor
                    }
                ];
            })
            ->values()
            ->toArray();
    }

    #[Computed]
    public function availableTimeSlots()
    {
        $slots = [];
        for ($h = 8; $h <= 19; $h++) {
            $slots[] = sprintf('%02d:00', $h);
            $slots[] = sprintf('%02d:30', $h);
        }
        return $slots;
    }

    public function book(int $resourceId, ReservationService $service)
    {
        $resource = Resource::findOrFail($resourceId);
        $isFullDay = in_array($resource->type, ['seat', 'spot', 'car']);

        $start = $isFullDay ? Carbon::parse($this->date)->startOfDay() : Carbon::parse("{$this->date} {$this->startTime}");
        $end = $isFullDay ? Carbon::parse($this->date)->endOfDay() : Carbon::parse("{$this->date} {$this->endTime}");

        try {
            $service->createReservation(auth()->user(), $resource, $start, $end, $isFullDay);
            $this->dispatch('toast', message: 'رزرو با موفقیت انجام شد', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function cancel(int $reservationId, ReservationService $service)
    {
        try {
            $service->cancelReservation(Reservation::findOrFail($reservationId), auth()->user());
            $this->dispatch('toast', message: 'رزرو با موفقیت لغو شد', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function mount()
    {
        $this->date = now()->toDateString();
    }

    public function render()
    {
        return view('livewire.dashboard.reservation.index', ['tabs' => Resource::getTabs()])
            ->extends('layouts.app')
            ->section('content');
    }

    public function resetFilters()
    {
        $this->filterFloor = null;
        $this->resourcesLimit = 6;
        unset($this->resources);
        unset($this->totalResources);
    }

    #[Computed]
    public function resources()
    {
        $isFullDay = in_array($this->activeTab, ['seat', 'spot', 'car']);
        $start = $isFullDay ? Carbon::parse($this->date)->startOfDay() : Carbon::parse("{$this->date} {$this->startTime}");
        $end = $isFullDay ? Carbon::parse($this->date)->endOfDay() : Carbon::parse("{$this->date} {$this->endTime}");

        return Resource::where('type', $this->activeTab)
            ->where('status', 'active')
            ->when($this->filterFloor, fn($q, $floor) => $q->where('metadata->floor', $floor))
            ->whereDoesntHave('reservations', function ($q) use ($start, $end) {
                $q->whereIn('status', ['active', 'released'])
                  ->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->limit($this->resourcesLimit)
            ->get();
    }

    #[Computed]
    public function totalResources()
    {
        $isFullDay = in_array($this->activeTab, ['seat', 'spot', 'car']);
        $start = $isFullDay ? Carbon::parse($this->date)->startOfDay() : Carbon::parse("{$this->date} {$this->startTime}");
        $end = $isFullDay ? Carbon::parse($this->date)->endOfDay() : Carbon::parse("{$this->date} {$this->endTime}");

        return Resource::where('type', $this->activeTab)
            ->where('status', 'active')
            ->when($this->filterFloor, fn($q, $floor) => $q->where('metadata->floor', $floor))
            ->whereDoesntHave('reservations', function ($q) use ($start, $end) {
                $q->whereIn('status', ['active', 'released'])
                  ->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->count();
    }

    public function setDate($date)
    {
        $this->date = $date;
        $this->resourcesLimit = 6;
        unset($this->resources);
        unset($this->totalResources);
    }

    public function setEndTime($time)
    {
        $this->endTime = $time;
        $this->resourcesLimit = 6;
        unset($this->resources);
        unset($this->totalResources);
    }

    public function setFloor($floor)
    {
        $this->filterFloor = $this->filterFloor === $floor ? null : $floor;
        $this->resourcesLimit = 6;
        unset($this->resources);
        unset($this->totalResources);
    }

    public function setStartTime($time)
    {
        $this->startTime = $time;
        $this->resourcesLimit = 6;
        unset($this->resources);
        unset($this->totalResources);
    }

    public function switchTab(string $tab): void
    {
        if ($this->activeTab === $tab) return;

        $this->activeTab = $tab;
        $this->resetFilters();
    }

    public function loadMoreResources()
    {
        $this->resourcesLimit += 6;
        unset($this->resources);
    }

    public function loadMoreUpcoming()
    {
        $this->upcomingLimit += 5;
        unset($this->upcomingReservations);
    }

    public function loadMorePrevious()
    {
        $this->previousLimit += 5;
        unset($this->previousReservations);
    }

    public function loadMoreCancelled()
    {
        $this->cancelledLimit += 5;
        unset($this->cancelledReservations);
    }

    #[Computed]
    public function upcomingReservations()
    {
        return Reservation::where('user_id', auth()->id())
            ->where('status', 'active')
            ->where('start_time', '>=', now())
            ->with('resource')
            ->orderBy('start_time')
            ->limit($this->upcomingLimit)
            ->get();
    }

    #[Computed]
    public function totalUpcoming()
    {
        return Reservation::where('user_id', auth()->id())
            ->where('status', 'active')
            ->where('start_time', '>=', now())
            ->count();
    }

    #[Computed]
    public function previousReservations()
    {
        return Reservation::where('user_id', auth()->id())
            ->where('status', 'active')
            ->where('start_time', '<', now())
            ->with('resource')
            ->orderByDesc('start_time')
            ->limit($this->previousLimit)
            ->get();
    }

    #[Computed]
    public function totalPrevious()
    {
        return Reservation::where('user_id', auth()->id())
            ->where('status', 'active')
            ->where('start_time', '<', now())
            ->count();
    }

    #[Computed]
    public function cancelledReservations()
    {
        return Reservation::where('user_id', auth()->id())
            ->whereIn('status', ['cancelled_user', 'cancelled_admin'])
            ->with('resource')
            ->orderByDesc('cancelled_at')
            ->limit($this->cancelledLimit)
            ->get();
    }

    #[Computed]
    public function totalCancelled()
    {
        return Reservation::where('user_id', auth()->id())
            ->whereIn('status', ['cancelled_user', 'cancelled_admin'])
            ->count();
    }
}
