<?php

namespace App\Livewire\Dashboard\Messaging;

use App\Livewire\Dashboard\Messaging\Actions\FetchMessagingUnreadAction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
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

    #[On('messaging-poll')]
    public function refreshCounts(): void
    {
        $this->loadCounts();
    }

    public function render()
    {
        return view('livewire.dashboard.messaging.switch-tabs', ['tabs' => $this->tabs()]);
    }

    protected function tabs(): array
    {
        return collect([
            ['id' => 'contacts', 'label' => 'پیام‌رسان', 'icon' => 'perm_contact_calendar', 'route' => route('contact'), 'unread' => $this->contactsUnread],
            ['id' => 'channels', 'label' => 'گروه‌ها', 'icon' => 'campaign', 'route' => route('channels'), 'unread' => $this->channelsUnread],
        ])
            ->reject(fn ($tab) => tenantOff($tab['id']))
            ->sortBy(fn ($tab) => $tab['id'] === $this->active ? 0 : 1)
            ->values()
            ->all();
    }

    protected function loadCounts(): void
    {
        $counts = app(FetchMessagingUnreadAction::class)->execute((int) Auth::id());

        $this->contactsUnread = $counts['contacts'];
        $this->channelsUnread = $counts['channels'];
    }
}