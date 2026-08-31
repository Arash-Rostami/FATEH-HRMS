<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Presentation\ProfilePresenter;
use App\Services\Menu\BadgeLegendCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Lazy]
class Main extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'info';

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
            'missingFields'  => $presenter->missingFieldLabels($user),
            'avatarImage'    => $presenter->avatarUrl($user),
            'position'       => $presenter->position($user),
            'departmentName' => $presenter->departmentName($user),
            'divisionName'   => $presenter->divisionName($user),
            'sectionName'    => $presenter->sectionName($user),
            'memberSince'    => $presenter->memberSince($user),
            'bioSnippet'     => $presenter->bioSnippet($user),
            'lastSeen'       => $presenter->lastSeen($user),
            'tabs'           => $presenter->tabs(),
            'badgeLegendGroups' => BadgeLegendCatalog::grouped(),
        ])->extends('layouts.app')->section('content');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.profile.placeholder')
            ->extends('layouts.app')
            ->section('content');
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }
}
