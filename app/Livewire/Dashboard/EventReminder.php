<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use Livewire\Component;

class EventReminder extends Component
{
    public function render()
    {
        $reminder = auth()->check() && !tenantOff('calendar') ? Event::nextReminderFor(auth()->user()) : null;

        return view('livewire.dashboard.event-reminder', ['reminder' => $reminder]);
    }
}
