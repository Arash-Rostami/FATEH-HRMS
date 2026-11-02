<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use Livewire\Attributes\Defer;
use Livewire\Attributes\On;
use Livewire\Component;

#[Defer]
class EventReminder extends Component
{
    public function placeholder()
    {
        return '<div class="hidden"></div>';
    }

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
