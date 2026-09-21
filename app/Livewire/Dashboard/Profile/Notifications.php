<?php

namespace App\Livewire\Dashboard\Profile;

use App\Services\Cache\ModelCacheVersion;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Notifications extends Component
{
    public string $activeSection = 'notifications';

    public int $perPage = 10;

    public function placeholder(): View
    {
        return view('livewire.dashboard.profile.notifications.placeholder');
    }

    public function switchTab(string $section): void
    {
        $this->activeSection = in_array($section, ['notifications', 'reminders'], true) ? $section : 'notifications';
    }

    #[Computed]
    public function notifications()
    {
        return Auth::user()->notifications()->latest()->paginate($this->perPage);
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function markRead(string $id): void
    {
        Auth::user()->notifications()->findOrFail($id)->markAsRead();

        ModelCacheVersion::bump(DatabaseNotification::class);

        unset($this->notifications);
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        ModelCacheVersion::bump(DatabaseNotification::class);

        unset($this->notifications);
    }

    public function render()
    {
        return view('livewire.dashboard.profile.notifications');
    }
}
