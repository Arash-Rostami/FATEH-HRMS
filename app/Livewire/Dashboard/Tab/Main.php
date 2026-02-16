<?php

namespace App\Livewire\Dashboard\Tab;

use Livewire\Attributes\Layout;
use Livewire\Component;


//#[Layout('layouts.app')]
class Main extends Component
{
    public $activeTab = 'overview';
    public $direction = 'up';

    public function getTabsProperty()
    {
        return [
            'overview' => [
                'component' => Overview::class,
                'label' => 'Overview',
                'icon' => 'home',
                'bg' => 'bg-surface-variant'
            ],
            'dashboard' => [
                'component' => Overview::class,
                'label' => 'Dashboard',
                'icon' => 'grid_view',
                'bg' => 'bg-secondary-container'
            ],
            'calendar' => [
                'component' => Calendar::class,
                'label' => 'Calendar',
                'icon' => 'calendar_month',
                'bg' => 'bg-tertiary-container'
            ],
            'gallery' => [
                'component' => Gallery::class,
                'label' => 'Gallery',
                'icon' => 'image',
                'bg' => 'bg-surface-container-high'
            ],
            'share' => [
                'component' => Share::class,
                'label' => 'Share',
                'icon' => 'share',
                'bg' => 'bg-primary-container'
            ],
            'analytics' => [
                'component' => Analytics::class,
                'label' => 'Analytics',
                'icon' => 'analytics',
                'bg' => 'bg-error-container'
            ],
            'profile' => [
                'component' => Profile::class,
                'label' => 'Profile',
                'icon' => 'person',
                'bg' => 'bg-surface-dim'
            ],
            'help' => [
                'component' => Help::class,
                'label' => 'Help',
                'icon' => 'help',
                'bg' => 'bg-info-container'
            ],
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.tab.main', [
            'currentTab' => $this->getTabsProperty()[$this->activeTab] ?? null,
            'tabs' => $this->getTabsProperty()
        ])->extends('layouts.app')->section('content');
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
}
