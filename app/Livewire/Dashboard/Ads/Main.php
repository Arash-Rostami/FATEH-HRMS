<?php

namespace App\Livewire\Dashboard\Ads;

use App\Models\Ad;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Traits\FocusOnRecord;

class Main extends Component
{
    use FocusOnRecord;
    protected function recordFocusType(): string { return 'ad'; }
    public string $activeFilter = 'active';
    public string $search = '';

    #[Computed]
    public function ads()
    {
        return Ad::query()
            ->when($this->activeFilter === 'active', fn(Builder $query) => $query->active())
            ->when($this->activeFilter === 'archived', fn(Builder $query) => $query->inactive())
            ->when($this->search, function (Builder $query) {
                $query->where('position', 'like', "%{$this->search}%")
                    ->orWhere('skill', 'like', "%{$this->search}%")
                    ->orWhere('certificate', 'like', "%{$this->search}%");
            })
            ->latest()
            ->get();
    }

    #[Computed]
    public function stats()
    {
        return [
            'active' => Ad::active()->count(),
            'archived' => Ad::inactive()->count(),
        ];
    }

    public function setFilter(string $filter)
    {
        $this->activeFilter = $filter;
    }

    public function render()
    {
        return view('livewire.dashboard.ads')
            ->extends('layouts.app')
            ->section('content');
    }
}
