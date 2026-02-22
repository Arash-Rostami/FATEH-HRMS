<?php

namespace App\Traits;

use App\Livewire\Dashboard\Tab\Calendar;
use App\Livewire\Dashboard\Tab\Faqs;
use App\Livewire\Dashboard\Tab\Feeds;
use App\Livewire\Dashboard\Tab\Gallery;
use App\Livewire\Dashboard\Tab\Links;
use App\Livewire\Dashboard\Tab\Overview;
use App\Livewire\Dashboard\Tab\Posts;
use App\Livewire\Dashboard\Tab\Reports;
use App\Livewire\Dashboard\Tab\Status;

trait HasDashboardTabs
{
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
}
