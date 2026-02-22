<?php

namespace App\Livewire\Dashboard\Tab\Profile;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Main extends Component
{
    public function render()
    {
        return view('livewire.dashboard.tab.profile.main');
    }
}
