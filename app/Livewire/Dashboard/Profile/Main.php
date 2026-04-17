<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Presentation\ProfilePresenter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Main extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'onboarding';

    public function confirmAction(string $event): void
    {
        $this->dispatch($event);
    }

    public function render()
    {
        $presenter = new ProfilePresenter();
        $user = $this->user;

        return view('livewire.dashboard.profile', [
            'user'           => $user,
            'completion'     => $presenter->completion($user),
            'avatarImage'    => $presenter->avatarUrl($user),
            'position'       => $presenter->position($user),
            'departmentName' => $presenter->departmentName($user),
            'memberSince'    => $presenter->memberSince($user),
            'lastSeen'       => $presenter->lastSeen($user),
            'tabs'           => $presenter->tabs(),
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
