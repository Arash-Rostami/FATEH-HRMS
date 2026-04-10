<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Actions\SendSmsAction;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Status extends Component
{
    public string $activeFilter = 'all';
    public string $search = '';
    public $showAboutModal = false;

    public function render()
    {
        return view('livewire.dashboard.tab.status.index');
    }

    public function sendSms(string $userId, SendSmsAction $action, SmsService $smsService)
    {
        $result = $action->execute($userId, $smsService);
        $this->dispatch('toast', message: $result['message'], type: $result['type']);
    }

    public function setFilter(string $filter)
    {
        $this->activeFilter = $this->activeFilter === $filter ? 'all' : $filter;
    }

    #[Computed]
    public function stats()
    {
        $counts = User::query()
            ->active()
            ->when($this->search, fn(Builder $query) => $query->search($this->search))
            ->selectRaw('presence, count(*) as count')
            ->toBase()
            ->groupBy('presence')
            ->get()
            ->mapWithKeys(fn($row) => [
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

    #[Computed]
    public function users()
    {
        return User::query()
            ->with(['profile.department'])
            ->active()
            ->when($this->activeFilter !== 'all', fn(Builder $query) => $query->where('presence', $this->activeFilter))
            ->when($this->search, fn(Builder $query) => $query->search($this->search))
            ->orderBy('name')
            ->get();
    }
}
