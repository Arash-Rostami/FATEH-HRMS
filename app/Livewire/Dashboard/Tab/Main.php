<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Ad;
use App\Models\Profile;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Main extends Component
{
    public $activeTab = 'home';
    public $direction = 'up';

    #[Computed]
    public function hasActiveAds()
    {
        return Ad::active()->exists();
    }

    #[Computed]
    public function hasRecentAboutMe()
    {
        return Profile::whereNotNull('about_me')
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->exists();
    }

    #[Computed]
    public function tabs()
    {
        return [
            'home' => [
                'component' => Home::class,
                'label' => 'خانه',
                'icon' => 'home',
                'bg' => 'bg-surface-variant',
                'badge' => false,
            ],
            'post' => [
                'component' => Posts::class,
                'label' => 'پست',
                'icon' => 'campaign',
                'bg' => 'bg-secondary-container',
                'badge' => false,
            ],
            'feed' => [
                'component' => Feeds::class,
                'label' => 'اخبار',
                'icon' => 'rss_feed',
                'bg' => 'bg-tertiary-container',
                'badge' => false,
            ],
            'calendar' => [
                'component' => Calendar::class,
                'label' => 'تقویم',
                'icon' => 'calendar_month',
                'bg' => 'bg-tertiary-container',
                'badge' => false,
            ],
            'status' => [
                'component' => Status::class,
                'label' => 'وضعیت',
                'icon' => 'badge',
                'bg' => 'bg-surface-container-low',
                'badge' => $this->hasRecentAboutMe,
            ],
            'gallery' => [
                'component' => Gallery::class,
                'label' => 'گالری',
                'icon' => 'photo_library',
                'bg' => 'bg-surface-container-high',
                'badge' => false,
            ],
            'reports' => [
                'component' => Reports::class,
                'label' => 'گزارش‌ها',
                'icon' => 'show_chart',
                'bg' => 'bg-secondary-container',
                'badge' => false,
            ],
            'links' => [
                'component' => Links::class,
                'label' => 'لینک‌ها',
                'icon' => 'open_in_new',
                'bg' => 'bg-surface-container-high',
                'badge' => false,
            ],
            'faqs' => [
                'component' => Faqs::class,
                'label' => 'پرسش‌های متداول',
                'icon' => 'help',
                'bg' => 'bg-secondary-container',
                'badge' => false,
            ],
        ];
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
        return view('livewire.dashboard.tab.main', [
            'currentTab' => $this->tabs[$this->activeTab] ?? null,
        ])->extends('layouts.app')->section('content');
    }

    #[On('switch-tab')]
    public function setTab($tab)
    {
        if ($tab === $this->activeTab) {
            return;
        }

        $tabsKeys = array_keys($this->tabs);

        if (!in_array($tab, $tabsKeys)) {
            return;
        }

        $currentIndex = array_search($this->activeTab, $tabsKeys);
        $newIndex = array_search($tab, $tabsKeys);

        $this->direction = $newIndex > $currentIndex ? 'up' : 'down';
        $this->activeTab = $tab;
    }
}
