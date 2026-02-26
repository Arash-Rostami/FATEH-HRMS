<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\User;
use App\Services\SmsService;
use App\Enums\PresenceStatus;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Status extends Component
{
    public string $activeFilter = 'all';
    public string $search = '';

    public function sendSms(string $userId, SmsService $smsService)
    {
        $user = User::with('profile')->findOrFail($userId);

        if ($user->sms_number) {
            $smsService->send($user->sms_number, " to be inserted later on ...");
            $this->dispatch('toast', message: 'پیامک با موفقیت ارسال شد', type: 'success');
        } else {
            $this->dispatch('toast', message: 'شماره تلفن همراه یافت نشد', type: 'error');
        }
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with(['profile.department'])
            ->active()
            ->when($this->activeFilter !== 'all', fn (Builder $query) => $query->where('presence', $this->activeFilter))
            ->when($this->search, fn (Builder $query) => $query->search($this->search))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function stats()
    {
        $counts = User::query()
            ->active()
            ->when($this->search, fn (Builder $query) => $query->search($this->search))
            ->selectRaw('presence, count(*) as count')
            ->toBase()
            ->groupBy('presence')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->presence => $row->count
            ])
            ->all();

        return [
            'onsite' => $counts['onsite'] ?? 0,
            'remote' => $counts['remote'] ?? 0,
            'busy' => $counts['busy'] ?? 0,
            'mission' => $counts['mission'] ?? 0,
        ];
    }

    public function setFilter(string $filter)
    {
        $this->activeFilter = $this->activeFilter === $filter ? 'all' : $filter;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.status.index');
    }
}
