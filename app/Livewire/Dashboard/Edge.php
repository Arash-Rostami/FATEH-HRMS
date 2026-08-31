<?php

namespace App\Livewire\Dashboard;

use App\Services\Menu\EdgeService;
use Livewire\Component;

class Edge extends Component
{
    public array $edges = [];

    public ?string $currentRoute = null;

    public function mount(): void
    {
        $this->currentRoute = request()->route()?->getName();
        $this->load();
    }

    public function load(): void
    {
        if (!auth()->check()) {
            $this->edges = [];

            return;
        }

        $this->edges = EdgeService::forUser((int) auth()->id());
    }

    public function dismiss(string $edgeKey, string $subjectId): void
    {
        if (!auth()->check()) return;

        EdgeService::dismiss((int) auth()->id(), $edgeKey, $subjectId);
        $this->load();
    }

    public function render()
    {
        return view('livewire.dashboard.edge');
    }
}
