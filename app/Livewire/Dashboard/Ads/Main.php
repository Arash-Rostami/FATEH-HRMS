<?php

namespace App\Livewire\Dashboard\Ads;

use App\Models\Ad;
use App\Traits\FocusOnRecord;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Main extends Component
{
    use FocusOnRecord;

    public string $activeFilter = 'active';
    public string $search = '';

    #[Computed]
    public function ads()
    {
        return Ad::query()
            ->when(
                $this->open,
                // FOCUS MODE: pin the listing to the single record chosen in the command palette.
                fn(Builder $q) => $q->whereKey($this->open),
                // NORMAL MODE: apply the active/archived filter + search.
                fn(Builder $q) => $q
                    ->when($this->activeFilter === 'active', fn(Builder $query) => $query->active())
                    ->when($this->activeFilter === 'archived', fn(Builder $query) => $query->inactive())
                    ->when($this->search, function (Builder $query) {
                        $query->where('position', 'like', "%{$this->search}%")
                            ->orWhere('skill', 'like', "%{$this->search}%")
                            ->orWhere('certificate', 'like', "%{$this->search}%");
                    })
                    ->latest()
            )
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.ads')
            ->extends('layouts.app')
            ->section('content');
    }

    public function setFilter(string $filter)
    {
        $this->open = null; // switching filter exits focus mode
        $this->activeFilter = $filter;
    }

    #[Computed]
    public function stats()
    {
        return [
            'active' => Ad::active()->count(),
            'archived' => Ad::inactive()->count(),
        ];
    }

    public function updatedSearch(): void
    {
        $this->open = null;
    }

    protected function recordFocusType(): string { return 'ad'; }
}
