<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use Livewire\Component;
use Throwable;

class Countdown extends Component
{
    public function dismiss()
    {
        abort_unless(auth()->check(), 401);

        try {
            $user = auth()->user();
            $user->setExtraValue('preferences.countdown_dismissed_at', today()->toDateString());
            $user->save();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function render()
    {
        $event = $this->resolveEvent();

        return view('livewire.dashboard.countdown', ['event' => $event]);
    }

    private function resolveEvent(): ?array
    {
        if (!auth()->check()) return null;

        $event = Event::activeCountdownEvent();
        $dismissed = auth()->user()->getPreference('countdown_dismissed_at') === today()->toDateString();

        return (empty($event) || $dismissed) ? null : $event;
    }
}
