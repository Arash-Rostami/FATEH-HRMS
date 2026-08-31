<?php

namespace App\Livewire\Dashboard\Navbar;

use App\Livewire\Dashboard\Navbar\Actions\FetchWeatherAction;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Component;

#[Defer]
class Weather extends Component
{
    #[Computed]
    public function weatherData(): array
    {
        return (new FetchWeatherAction())->execute();
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="flex items-center gap-2">
            <div class="w-[18px] h-[18px] rounded-full bg-[var(--md-sys-color-on-primary)]/25 animate-pulse"></div>
            <div class="w-8 h-3 rounded bg-[var(--md-sys-color-on-primary)]/25 animate-pulse"></div>
        </div>
        HTML;
    }

    public function render()
    {
        return view('livewire.dashboard.navbar.top.weather', [
            'weatherData' => $this->weatherData,
        ]);
    }
}
