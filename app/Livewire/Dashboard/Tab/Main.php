<?php

namespace App\Livewire\Dashboard\Tab;

use Livewire\Component;


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
            'post' => [
                'component' => Posts::class,
                'label' => 'پست',
                'icon' => 'newspaper',
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

    public function navigateTab(int $step)
    {
        $keys = array_keys($this->getTabsProperty());
        $currentIndex = array_search($this->activeTab, $keys);

        if ($currentIndex === false) return;

        $count = count($keys);

        $newIndex = ($currentIndex + $step + $count) % $count;

        $this->setTab($keys[$newIndex]);
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


        $this->direction = $newIndex > $currentIndex ? 'up' : 'down';

        $this->activeTab = $tabId;
    }
}
