<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use Livewire\Attributes\On;
use Livewire\Component;

class EventReminder extends Component
{
    #[On('event-reminder-changed')]
    public function refresh(): void
    {
    }

    public function render()
    {
        $reminder = auth()->check() && !tenantOff('calendar') ? Event::nextReminderFor(auth()->user()) : null;

        return view('livewire.dashboard.event-reminder', ['reminder' => $reminder]);
    }
}
