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
        <div class="flex items-center justify-center w-full h-full opacity-50">
            <span class="material-symbols-rounded animate-spin text-[18px]">progress_activity</span>
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
