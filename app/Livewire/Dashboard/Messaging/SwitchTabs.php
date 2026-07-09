<?php

namespace App\Livewire\Dashboard\Messaging;

use App\Livewire\Dashboard\Messaging\Actions\FetchMessagingUnreadAction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SwitchTabs extends Component
{
    public string $active = 'contacts';

    public int $contactsUnread = 0;

    public int $channelsUnread = 0;

    public function mount(string $active = 'contacts'): void
    {
        $this->active = $active;
        $this->loadCounts();
    }

    public function refreshCounts(): void
    {
        $this->loadCounts();
    }

    public function render()
    {
        return view('livewire.dashboard.messaging.switch-tabs');
    }

    protected function loadCounts(): void
    {
        $counts = app(FetchMessagingUnreadAction::class)->execute((int) Auth::id());

        $this->contactsUnread = $counts['contacts'];
        $this->channelsUnread = $counts['channels'];
    }
}