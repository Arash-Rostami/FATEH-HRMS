<?php

namespace App\Livewire\Dashboard\Dms;

use App\Models\DMS;
use Livewire\Component;

class Main extends Component
{
    public $dms;

    public function render()
    {
        $this->dms = Dms::all();

        return view('livewire.dashboard.dms.index')
            ->extends('layouts.app')
            ->section('content');
    }
}
