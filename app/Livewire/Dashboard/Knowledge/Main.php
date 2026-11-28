<?php

namespace App\Livewire\Dashboard\Knowledge;

use Illuminate\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Lazy]
class Main extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'reports';

    #[Url]
    public ?int $open = null;

    public function mount(): void
    {
        abort_if(tenantOff('knowledge'), 404);

        if (!in_array($this->activeTab, $this->tabs(), true)) {
            $this->activeTab = 'reports';
        }
    }

    public function updatedActiveTab(string $value): void
    {
        if (!in_array($value, $this->tabs(), true)) {
            $this->activeTab = 'reports';
        }

        $this->open = null;
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.knowledge.placeholder');
    }

    public function render(): View
    {
        return view('livewire.dashboard.knowledge');
    }

    private function tabs(): array
    {
        return tenantOff('knowledge:media') ? ['reports'] : ['reports', 'media'];
    }
}
