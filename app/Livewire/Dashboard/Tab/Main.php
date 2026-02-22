<?php

namespace App\Livewire\Dashboard\Tab;

use App\Traits\HasDashboardTabs;
use Livewire\Component;
use Livewire\Attributes\Url;

class Main extends Component
{
    use HasDashboardTabs;

    #[Url(as: 'tab')]
    public $activeTab = 'overview';

    public $direction = 'up';

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
