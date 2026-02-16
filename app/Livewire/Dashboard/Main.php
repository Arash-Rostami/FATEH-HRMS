<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Main extends Component
{
    public $activeTab = 'overview';
    public $direction = 'up'; // 'up' or 'down'

    // Define tabs configuration
    public function getTabsProperty()
    {
        return [
            'overview' => [
                'component' => \App\Livewire\Dashboard\Overview::class,
                'label' => 'Overview',
                'icon' => 'home', // Using 'home' icon for Overview/Home
                'bg' => 'bg-surface-variant' // Example dynamic background
            ],
            'dashboard' => [
                'component' => \App\Livewire\Dashboard\Overview::class, // Or a separate Dashboard component if intended
                'label' => 'Dashboard',
                'icon' => 'grid_view',
                'bg' => 'bg-secondary-container'
            ],
            'calendar' => [
                'component' => \App\Livewire\Dashboard\Calendar::class,
                'label' => 'Calendar',
                'icon' => 'calendar_month',
                'bg' => 'bg-tertiary-container'
            ],
            'gallery' => [
                'component' => \App\Livewire\Dashboard\Gallery::class,
                'label' => 'Gallery',
                'icon' => 'image',
                'bg' => 'bg-surface-container-high'
            ],
            'share' => [
                'component' => \App\Livewire\Dashboard\Share::class,
                'label' => 'Share',
                'icon' => 'share',
                'bg' => 'bg-primary-container'
            ],
            'analytics' => [
                'component' => \App\Livewire\Dashboard\Analytics::class,
                'label' => 'Analytics',
                'icon' => 'analytics',
                'bg' => 'bg-error-container'
            ],
            'profile' => [
                'component' => \App\Livewire\Dashboard\Profile::class,
                'label' => 'Profile',
                'icon' => 'person',
                'bg' => 'bg-surface-dim'
            ],
            'help' => [
                'component' => \App\Livewire\Dashboard\Help::class,
                'label' => 'Help',
                'icon' => 'help',
                'bg' => 'bg-info-container'
            ],
        ];
    }

    public function setTab($tabId)
    {
        if ($tabId === $this->activeTab) {
            return;
        }

        $tabsKeys = array_keys($this->getTabsProperty());
        $currentIndex = array_search($this->activeTab, $tabsKeys);
        $newIndex = array_search($tabId, $tabsKeys);

        // Determine direction
        // If moving down the list (index increases), content slides UP (new comes from bottom)
        // If moving up the list (index decreases), content slides DOWN (new comes from top)
        $this->direction = $newIndex > $currentIndex ? 'up' : 'down';

        $this->activeTab = $tabId;
    }

    public function render()
    {
        return view('livewire.dashboard.main', [
            'currentTab' => $this->getTabsProperty()[$this->activeTab] ?? null,
            'tabs' => $this->getTabsProperty()
        ]);
    }
}
