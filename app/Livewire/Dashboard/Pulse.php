<?php

namespace App\Livewire\Dashboard;

use App\Services\Menu\PulseService;
use Livewire\Attributes\Computed;
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

        $result = ['events' => PulseService::changedEvents($known, $fingerprint), 'fingerprint' => $fingerprint];

        if (isset($fingerprint['presence']) && ($known['presence'] ?? null) !== $fingerprint['presence']) {
            $result['presence'] = PulseService::presenceMap($fingerprint['presence']);
        }

        return $result;
    }

    #[Computed]
    public function presenceMeta(): array
    {
        return PulseService::presenceMeta();
    }

    public function render(): string
    {
        return <<<'HTML'
        <div x-data="pulse(@js($fingerprint))" class="hidden" data-presence-meta="{{ json_encode($this->presenceMeta()) }}" data-notification-sound="{{ asset('build/assets/audio/notification.mp3') }}"></div>
        HTML;
    }
}
