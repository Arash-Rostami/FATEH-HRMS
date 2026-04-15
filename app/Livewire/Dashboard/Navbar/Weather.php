<?php

namespace App\Livewire\Dashboard\Navbar;

use App\Livewire\Dashboard\Navbar\Actions\FetchWeatherAction;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Weather extends Component
{
    #[Computed(seconds: 14400, cache: true, key: 'weather.tehran')]
    public function weatherData(): array
    {
        return (new FetchWeatherAction())->execute();
    }

    public function render()
    {
        return view('livewire.dashboard.navbar.top.weather');
    }
}
