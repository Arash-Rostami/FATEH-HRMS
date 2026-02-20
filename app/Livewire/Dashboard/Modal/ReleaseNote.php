<?php

namespace App\Livewire\Dashboard\Modal;

use Livewire\Component;

class ReleaseNote extends Component
{
    public bool $isOpen = false;

    protected $listeners = ['open-release-note' => 'open'];

    public function open()
    {
        $this->isOpen = true;
    }

    public function render()
    {
        return view('livewire.dashboard.modal.release-note');
    }
}
