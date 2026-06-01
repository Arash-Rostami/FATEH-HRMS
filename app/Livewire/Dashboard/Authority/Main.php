<?php

namespace App\Livewire\Dashboard\Authority;

use App\Livewire\Dashboard\Authority\Presentation\AuthorityPresenter;
use App\Models\Authority;
use App\Models\Department;
use App\Traits\FocusOnRecord;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Main extends Component
{
    use FocusOnRecord;
    use WithPagination;

    public string $activeDept = '';
    public string $search = '';
    public string $activeTab = 'manager';
    public int $perPage = 20;

    #[Computed]
    public function authorities()
    {
        return Authority::with('user.profile')
            ->when(
                $this->open,
                // FOCUS MODE: pin the listing to the single record chosen in the command palette.
                fn($q) => $q->whereKey($this->open),
                // NORMAL MODE: scope to the selected department + search.
                fn($q) => $q
                    ->where('department_id', $this->activeDept)
                    ->search($this->search)
                    ->latest()
            )
            ->paginate($this->perPage);
    }

    #[Computed]
    public function currentDepartment(): ?Department
    {
        return $this->departments->firstWhere('code', $this->activeDept);
    }

    #[Computed(seconds: 3600, cache: true, key: 'authority-departments')]
    public function departments()
    {
        return Department::whereHas('authorities')
            ->select(['code', 'name', 'description'])
            ->orderBy('name')
            ->get();
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function mount(): void
    {
        if ($this->open) {
            $this->perPage = max($this->perPage, 50);
        }
        $this->activeDept = auth()->user()->profile?->department_id ?? '';
    }

    public function render()
    {
        return view('livewire.dashboard.authority', [
            'presenter' => new AuthorityPresenter(),
        ])->extends('layouts.app')->section('content');
    }

    public function setDept(string $code): void
    {
        $this->open = null;
        $this->activeDept = $code;
        $this->resetPage();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[Computed(seconds: 3600, cache: true, key: 'authority-global-count')]
    public function totalCount(): int
    {
        return Authority::count();
    }

    public function updatedSearch(): void
    {
        $this->open = null;
        $this->resetPage();
    }

    protected function recordFocusType(): string
    {
        return 'authority';
    }
}
