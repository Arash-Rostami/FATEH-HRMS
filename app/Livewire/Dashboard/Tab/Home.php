<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Presentation\TabPresenter;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Home extends Component
{

    public ?array $tools = [];

    #[Computed]
    public function modules(): array
    {
        return Config::get('modules', []);
    }

    public function render()
    {
        return view('livewire.dashboard.tab.home', [
            'tools' => TabPresenter::class,
        ]);
    }
}
