<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Calendar;
use App\Livewire\Dashboard\Tab\Faqs;
use App\Livewire\Dashboard\Tab\Feeds;
use App\Livewire\Dashboard\Tab\Gallery;
use App\Livewire\Dashboard\Tab\Home;
use App\Livewire\Dashboard\Tab\Links;
use App\Livewire\Dashboard\Tab\Posts;
use App\Livewire\Dashboard\Tab\Reports;
use App\Livewire\Dashboard\Tab\Status;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Tabs extends Component
{
    #[Url(as: 'tab')]
    public $activeTab = 'home';

    public $direction = 'up';

    public function mount(): void
    {
        $this->activeTab = $this->normalizeTab($this->activeTab);
    }

    public function navigateTab(int $step)
    {
        $keys = array_keys($this->tabs);
        $currentIndex = array_search($this->activeTab, $keys);

        if ($currentIndex === false) return;

        $count = count($keys);
        $newIndex = ($currentIndex + $step + $count) % $count;

        $this->setTab($keys[$newIndex]);
    }

    public function render()
    {
        return view('livewire.dashboard.tabs', [
            'currentTab' => $this->tabs[$this->activeTab] ?? null,
        ])->extends('layouts.app')->section('content');
    }

    #[On('switch-tab')]
    public function setTab($tab)
    {
        $tab = $this->normalizeTab($tab);
        if ($tab === $this->activeTab) return;

        $keys = array_keys($this->tabs);
        $this->direction = array_search($tab, $keys) > array_search($this->activeTab, $keys) ? 'up' : 'down';
        $this->activeTab = $tab;
    }

    private function normalizeTab(?string $tab): string
    {
        return $tab && array_key_exists($tab, $this->tabs) ? $tab : 'home';
    }

    #[Computed]
    public function tabs()
    {
        return [
            'home' => [
                'component' => Home::class,
                'label' => 'خانه',
                'icon' => 'home',
                'bg' => 'bg-surface-variant'
            ],
            'post' => [
                'component' => Posts::class,
                'label' => 'اعلانات',
                'icon' => 'campaign',
                'bg' => 'bg-secondary-container',
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
                'bg' => 'bg-tertiary-container',
                'badge' => 'shared-events',
            ],
            'status' => [
                'component' => Status::class,
                'label' => 'وضعیت',
                'icon' => 'badge',
                'bg' => 'bg-surface-container-low'
            ],
            'gallery' => [
                'component' => Gallery::class,
                'label' => 'گالری',
                'icon' => 'photo_library',
                'bg' => 'bg-surface-container-high'
            ],
            'reports' => [
                'component' => Reports::class,
                'label' => 'گزارش‌ها',
                'icon' => 'show_chart',
                'bg' => 'bg-secondary-container'
            ],
            'links' => [
                'component' => Links::class,
                'label' => 'لینک‌ها',
                'icon' => 'open_in_new',
                'bg' => 'bg-surface-container-high'
            ],
            'faqs' => [
                'component' => Faqs::class,
                'label' => 'پرسش‌های متداول',
                'icon' => 'help',
                'bg' => 'bg-info-container'
            ],
        ];
    }
}
