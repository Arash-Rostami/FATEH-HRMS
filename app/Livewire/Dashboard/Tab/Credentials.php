<?php

namespace App\Livewire\Dashboard\Tab;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Credentials extends Component
{
    public string $search = '';

    public function getCredentialsProperty()
    {
        return Auth::user()->credentials()
            ->when($this->search, function ($query) {
                $query->where('app_name', 'like', '%' . $this->search . '%')
                    ->orWhere('username', 'like', '%' . $this->search . '%');
            })
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.profile.credentials', [
            'credentials' => $this->credentials
        ]);
    }
}
