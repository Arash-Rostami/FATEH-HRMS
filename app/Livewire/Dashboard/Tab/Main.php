<?php

namespace App\Livewire\Dashboard\Tab;

use Livewire\Attributes\On;
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
                'label' => 'مروری',
                'icon' => 'home',
                'bg' => 'bg-surface-variant'
            ],
            'post' => [
                'component' => Posts::class,
                'label' => 'پست',
                'icon' => 'newspaper',
                'bg' => 'bg-secondary-container'
            ],
            'feed' => [
                'component' => Feeds::class,
                'label' => 'اخبار',
                'icon' => 'rss_feed',
                'bg' => 'bg-tertiary-container'
            ],
            'calendar' => [
                'component' => Calendar::class,
                'label' => 'تقویم',
                'icon' => 'calendar_month',
                'bg' => 'bg-tertiary-container'
            ],
            'gallery' => [
                'component' => Gallery::class,
                'label' => 'گالری',
                'icon' => 'image',
                'bg' => 'bg-surface-container-high'
            ],
            'reports' => [
                'component' => Reports::class,
                'label' => 'گزارش‌ها',
                'icon' => 'description',
                'bg' => 'bg-secondary-container'
            ],
            'links' => [
                'component' => Links::class,
                'label' => 'لینک‌ها',
                'icon' => 'open_in_new',
                'bg' => 'bg-surface-container-high'
            ],
            'status' => [
                'component' => Status::class,
                'label' => 'وضعیت',
                'icon' => 'hub',
                'bg' => 'bg-surface-container-low'
            ],
            'faqs' => [
                'component' => Faqs::class,
                'label' => 'پرسش‌های متداول',
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

    #[On('switch-tab')]
    public function setTab($tab)
    {
        $tabId = $tab;

        if ($tabId === $this->activeTab) {
            return;
        }

        $tabsKeys = array_keys($this->getTabsProperty());

        if (!in_array($tabId, $tabsKeys)) {
            return;
        }

        $currentIndex = array_search($this->activeTab, $tabsKeys);
        $newIndex = array_search($tabId, $tabsKeys);

        $this->direction = $newIndex > $currentIndex ? 'up' : 'down';

        $this->activeTab = $tabId;
    }
}
