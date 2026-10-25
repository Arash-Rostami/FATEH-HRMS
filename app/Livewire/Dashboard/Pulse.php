<?php

namespace App\Livewire\Dashboard;

use App\Services\Menu\PulseService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Pulse extends Component
{
    #[Locked]
    public array $fingerprint = [];

    public function mount(): void
    {
        if (auth()->check()) {
            $this->fingerprint = PulseService::fingerprint(auth()->user());
        }
    }

    public function pulse(array $known): array
    {
        $this->skipRender();

        $fingerprint = auth()->check() ? PulseService::fingerprint(auth()->user()) : [];

        return ['events' => PulseService::changedEvents($known, $fingerprint), 'fingerprint' => $fingerprint];
    }

    public function render(): string
    {
        return <<<'HTML'
        <div x-data="pulse(@js($fingerprint))" class="hidden"></div>
        HTML;
    }
}
