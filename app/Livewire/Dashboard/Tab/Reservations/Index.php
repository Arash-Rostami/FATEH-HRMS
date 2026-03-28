<?php

namespace App\Livewire\Dashboard\Tab\Reservations;

use App\Models\Reservation;
use App\Models\Resource;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    public $type = 'seat'; // seat, spot, car, appointment
    public $date;
    public $startTime = '09:00';
    public $endTime = '10:00';
    public $filterFloor = null;

    public function mount()
    {
        $this->date = now()->toDateString();
    }

    #[Computed]
    public function availableDates()
    {
        $dates = [];
        $today = now();
        for ($i = 0; $i < 14; $i++) {
            $date = $today->copy()->addDays($i);
            $jalali = Jalalian::fromCarbon($date);
            $dates[] = [
                'value' => $date->toDateString(),
                'day' => $jalali->format('l'), // شنبه
                'date' => $jalali->format('d'), // ۲۵
                'month' => $jalali->format('F'), // مهر
                'isToday' => $i === 0,
            ];
        }
        return $dates;
    }

    #[Computed]
    public function availableTimeSlots()
    {
        $slots = [];
        for ($h = 8; $h <= 18; $h++) {
            $slots[] = sprintf('%02d:00', $h);
            $slots[] = sprintf('%02d:30', $h);
        }
        return $slots;
    }

    #[Computed]
    public function availableFloors()
    {
        return [
            ['value' => '1', 'label' => 'طبقه ۱'],
            ['value' => '2', 'label' => 'طبقه ۲'],
            ['value' => '3', 'label' => 'طبقه ۳'],
        ];
    }

    #[Computed]
    public function resources()
    {
        $query = Resource::where('type', $this->type)->where('status', 'active');

        if ($this->filterFloor) {
             // Use MySQL JSON extraction operator ->> for better performance
             $query->where('metadata->floor', $this->filterFloor);
        }

        return $query->get();
    }

    #[Computed]
    public function userReservations()
    {
        return Reservation::where('user_id', Auth::id())
             ->where('status', 'active')
             ->whereDate('start_time', '>=', now()->toDateString())
             ->with('resource')
             ->orderBy('start_time')
             ->get();
    }

    public function setType($type)
    {
        $this->type = $type;
        $this->resetFilters();
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function setStartTime($time)
    {
        $this->startTime = $time;
    }

    public function setEndTime($time)
    {
        $this->endTime = $time;
    }

    public function setFloor($floor)
    {
        // Toggle behavior
        if ($this->filterFloor === $floor) {
            $this->filterFloor = null;
        } else {
            $this->filterFloor = $floor;
        }
    }

    public function resetFilters()
    {
        $this->filterFloor = null;
    }

    public function book(int $resourceId)
    {
        $resource = Resource::findOrFail($resourceId);
        $isFullDay = in_array($resource->type, ['seat', 'spot', 'car']);

        if ($isFullDay) {
            $start = Carbon::parse($this->date)->startOfDay();
            $end = Carbon::parse($this->date)->endOfDay();
        } else {
            $start = Carbon::parse($this->date . ' ' . $this->startTime);
            $end = Carbon::parse($this->date . ' ' . $this->endTime);
        }

        $service = new ReservationService();
        try {
            $service->createReservation(Auth::user(), $resource, $start, $end, $isFullDay);

            // Dispatch to existing global UI notification pattern
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'رزرو با موفقیت انجام شد'
            ]);

            // Clear cache for computed properties implicitly by Livewire state change
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function cancel(int $reservationId)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $service = new ReservationService();
        try {
            $service->cancelReservation($reservation, Auth::user());
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'رزرو لغو شد'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.tab.reservations.index');
    }
}
