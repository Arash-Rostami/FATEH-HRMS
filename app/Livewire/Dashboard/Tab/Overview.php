<?php

namespace App\Livewire\Dashboard\Tab;

use Livewire\Component;

class Overview extends Component
{

    public function placeholder()
    {
        return view('livewire.dashboard.tab.placeholder');
    }

    public function render()
    {
        return view('livewire.dashboard.tab.overview');
    }
}
