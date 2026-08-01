<?php

namespace App\Livewire\Dashboard\Tab;

use App\Enums\PresenceStatus;
use App\Livewire\Dashboard\Tab\Actions\SendSmsAction;
use App\Models\User;
use App\Services\SmsService;
use App\Services\User\UserKeyGrouper;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Status extends Component
{
    public string $activeFilter = 'all';
    public string $activeClassifier = 'all';
    public string $search = '';
    public $showAboutModal = false;

    public function render()
    {
        return view('livewire.dashboard.tab.status');
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
    public function classifierGroups(): array
    {
        return collect(UserKeyGrouper::map())
            ->reject(fn(array $g) => UserKeyGrouper::isHidden($g['norm']))
            ->mapWithKeys(fn(array $g) => [
                $g['norm'] => [
                    'label' => $g['label'],
                    'values' => UserKeyGrouper::distinctValues($g['variants']),
                ],
            ])
            ->all();
    }

    #[Computed]
    public function stats()
    {
        $counts = User::query()
            ->visibleOnBoard()
            ->when($this->search !== '', fn(Builder $query) => $query->search($this->search))
            ->when($this->activeClassifier !== 'all', fn(Builder $query) => $this->applyClassifier($query))
            ->selectRaw('presence, count(*) as count')
            ->toBase()
            ->groupBy('presence')
            ->get()
            ->mapWithKeys(fn($row) => [
                $row->presence => $row->count
            ])
            ->all();

        return collect(PresenceStatus::cases())
            ->mapWithKeys(fn(PresenceStatus $s) => [$s->value => $counts[$s->value] ?? 0])
            ->all();
    }

    #[Computed]
    public function users()
    {
        $rankOrder = implode("','", array_reverse(array_keys(User::RANKS)));

        User::primeTodaysDeskCache();

        return User::query()
            ->with(['profile.department', 'profile.details'])
            ->visibleOnBoard()
            ->when($this->activeFilter !== 'all', fn(Builder $query) => $query->where('presence', $this->activeFilter))
            ->when($this->activeClassifier !== 'all', fn(Builder $query) => $this->applyClassifier($query))
            ->when($this->search !== '', fn(Builder $query) => $query->search($this->search))
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->select('users.*')
            ->orderByRaw("FIELD(profiles.position, '{$rankOrder}') DESC")
            ->orderByDesc('users.last_seen')
            ->get();
    }

    private function applyClassifier(Builder $query): Builder
    {
        [$norm, $value] = array_pad(explode('|', $this->activeClassifier, 2), 2, '');

        if (UserKeyGrouper::isHidden($norm)) {
            return $query;
        }

        $group = collect(UserKeyGrouper::map())->firstWhere('norm', $norm);

        return $group ? UserKeyGrouper::applyFilter($query, $group['variants'], $value) : $query;
    }
}
