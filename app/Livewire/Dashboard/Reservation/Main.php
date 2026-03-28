<?php

namespace App\Livewire\Dashboard\Reservation;

use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Main extends Component
{
    public $resources = [];
    public $selectedType = 'seat';
    public $selectedDate;
    public $errorMessage = '';

    protected $listeners = ['refresh-resources' => 'fetchResources'];

    public function mount()
    {
        $this->selectedDate = Carbon::now()->toDateString();
        $this->fetchResources();
    }

    public function fetchResources()
    {
        // Add basic logic to get resources filtered by type.
        // Needs robust availability checking logic here based on 'reservations' table and 'parent_id' releases.
        $this->resources = Resource::where('type', $this->selectedType)
            ->where('status', 'active')
            ->get();
    }

    public function bookResource($resourceId)
    {
        $user = Auth::user();

        if ($user->status !== 'active') {
            $this->errorMessage = 'Your account is currently inactive.';
            return;
        }

        $bookingPermissions = $user->booking ?? [];
        if (!isset($bookingPermissions[$this->selectedType]) || !$bookingPermissions[$this->selectedType]) {
            $this->errorMessage = 'You do not have permission to book this resource type.';
            return;
        }

        // Cancellation Limit Validation (1/4 of max)
        $cancellationLimit = floor($user->maximum / 4);
        $recentCancellations = Reservation::where('user_id', $user->id)
            ->where('status', 'cancelled_user')
            ->where('cancelled_at', '>=', Carbon::now()->subDays(30))
            ->count();

        if ($recentCancellations >= $cancellationLimit) {
            $this->errorMessage = 'Cancellation limit reached for the last 30 days.';
            return;
        }

        // ... Existing Bookings Check, Policy Check ...

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resourceId,
            'start_time' => Carbon::parse($this->selectedDate)->startOfDay(),
            'end_time' => Carbon::parse($this->selectedDate)->endOfDay(),
            'status' => 'active',
        ]);

        $this->fetchResources();
    }

    public function render()
    {
        return view('livewire.dashboard.reservation.index')->extends('layouts.app')->section('content');
    }
}
