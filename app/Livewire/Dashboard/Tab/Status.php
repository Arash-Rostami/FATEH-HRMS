<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Status extends Component
{
    public string $activeFilter = 'onsite';
    public string $search = '';

    #[Computed]
    public function users()
    {
        return User::query()
            ->with(['profile.department'])
            ->where('status', 'active')
            ->when($this->activeFilter !== 'all', function (Builder $query) {
                $query->where('presence', $this->activeFilter);
            })
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('profile', function (Builder $profileQuery) {
                            $profileQuery->where('position', 'like', '%' . $this->search . '%')
                                ->orWhereHas('department', function (Builder $deptQuery) {
                                    $deptQuery->where('name', 'like', '%' . $this->search . '%');
                                });
                        });
                });
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function stats()
    {
        // Using distinct queries for clarity and avoiding complex grouping logic
        // Alternatively could use groupBy('presence')->selectRaw('presence, count(*) as count')->pluck('count', 'presence')
        // But this is cleaner for specific keys

        $counts = User::query()
            ->where('status', 'active')
            ->selectRaw('presence, count(*) as count')
            ->groupBy('presence')
            ->pluck('count', 'presence')
            ->toArray();

        return [
            'onsite' => $counts['onsite'] ?? 0,
            'remote' => $counts['remote'] ?? 0,
            'busy' => $counts['busy'] ?? 0,
            'mission' => $counts['mission'] ?? 0,
        ];
    }

    public function setFilter(string $filter)
    {
        $this->activeFilter = $filter;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.status.index');
    }
}
