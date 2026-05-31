<?php

namespace App\Livewire\Dashboard\Profile;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Traits\FocusOnRecord;

class Credentials extends Component
{
    use FocusOnRecord;
    public string $search = '';

    #[Computed]
    public function credentials()
    {
        return Auth::user()->credentials()
            ->when($this->search, fn($q) => $q
                ->where('app_name', 'like', "%{$this->search}%")
                ->orWhere('username', 'like', "%{$this->search}%")
            )
            ->get();
    }

    #[Computed]
    public function hasAnyCredentials(): bool
    {
        return Auth::user()->credentials()->exists();
    }

    public function render()
    {
        return view('livewire.dashboard.profile.credentials');
    }
}
