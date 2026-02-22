<?php

namespace App\Livewire\Dashboard\Tab;

use App\Traits\HasDashboardTabs;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Url;

class Profile extends Component
{
    use HasDashboardTabs;

    public $activeTab = 'profile';

    #[Url(as: 'view')]
    public $activeSubTab = 'info';

    public function setTab($tabId)
    {
        return redirect()->route('dashboard', ['tab' => $tabId]);
    }

    public function setSubTab($subTab)
    {
        $this->activeSubTab = $subTab;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.profile.index', [
            'user' => Auth::user(),
        ])->extends('layouts.app')->section('content');
    }
}
