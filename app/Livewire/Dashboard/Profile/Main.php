<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Presentation\ProfilePresenter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Main extends Component
{
    #[Url]
    public string $activeTab = 'onboarding';

    public function confirmAction(string $event): void
    {
        $this->dispatch($event);
    }

    public function render()
    {
        return view('livewire.dashboard.profile.index', [
            'user' => $this->user, 'completion' => (new ProfilePresenter())->completion($this->user),
        ])->extends('layouts.app')->section('content');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }
}
