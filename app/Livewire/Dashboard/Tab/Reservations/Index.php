<?php

namespace App\Livewire\Dashboard\Tab\Reservations;

use App\Models\Reservation;
use App\Models\Resource;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    public $type = 'seat'; // seat, spot, car, appointment
    public $date;
    public $startTime = '09:00';
    public $endTime = '10:00';
    public $filterFloor = null;
    public $filterMetadata = [];

    public function mount()
    {
        $this->date = now()->toDateString();
    }

    #[Computed]
    public function resources()
    {
        $query = Resource::where('type', $this->type)->where('status', 'active');

        if ($this->filterFloor) {
             // Example JSON search: metadata->floor
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
             ->get();
    }

    public function setType($type)
    {
        $this->type = $type;
        $this->resetFilters();
    }

    public function resetFilters()
    {
        $this->filterFloor = null;
        $this->filterMetadata = [];
    }

    public function book(int $resourceId)
    {
        $resource = Resource::findOrFail($resourceId);
        $isFullDay = in_array($resource->type, ['seat', 'spot', 'car']);

        if ($isFullDay) {
            $start = Carbon::parse($this->date)->startOfDay();
            $end = Carbon::parse($this->date)->endOfDay();
        } else {
            // It's an appointment (hourly metric)
            $start = Carbon::parse($this->date . ' ' . $this->startTime);
            $end = Carbon::parse($this->date . ' ' . $this->endTime);
        }

        $service = new ReservationService();
        try {
            $service->createReservation(Auth::user(), $resource, $start, $end, $isFullDay);
            $this->dispatch('notify', ['message' => 'Reservation successful', 'type' => 'success']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => $e->getMessage(), 'type' => 'error']);
        }
    }

    public function cancel(int $reservationId)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $service = new ReservationService();
        try {
            $service->cancelReservation($reservation, Auth::user());
            $this->dispatch('notify', ['message' => 'Reservation cancelled successfully', 'type' => 'success']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => $e->getMessage(), 'type' => 'error']);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.tab.reservations.index');
    }
}
