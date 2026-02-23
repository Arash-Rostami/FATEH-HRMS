<?php

namespace App\Livewire\Dashboard\Tab;

use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Overview extends Component
{
    #[Computed]
    public function modules(): array
    {
        return Config::get('modules', []);
    }

    #[Computed]
    public function tools(): array
    {
        return [
            [
                'title' => 'پروفایل من',
                'icon' => 'person',
                'action' => 'profile',
                'color' => 'var(--md-sys-color-primary)',
                'bg' => 'var(--md-sys-color-primary-container)',
                'text' => 'var(--md-sys-color-on-primary-container)',
            ],
            [
                'title' => 'تقویم کاری',
                'icon' => 'calendar_month',
                'action' => 'calendar',
                'color' => 'var(--md-sys-color-tertiary)',
                'bg' => 'var(--md-sys-color-tertiary-container)',
                'text' => 'var(--md-sys-color-on-tertiary-container)',
            ],
            [
                'title' => 'گزارشات',
                'icon' => 'show_chart',
                'action' => 'reports',
                'color' => 'var(--md-sys-color-secondary)',
                'bg' => 'var(--md-sys-color-secondary-container)',
                'text' => 'var(--md-sys-color-on-secondary-container)',
            ],
            [
                'title' => 'وضعیت همکاران',
                'icon' => 'group',
                'action' => 'status',
                'color' => 'var(--md-sys-color-error)',
                'bg' => 'var(--md-sys-color-error-container)',
                'text' => 'var(--md-sys-color-on-error-container)',
            ],
        ];
    }

    public function placeholder()
    {
        return view('livewire.dashboard.tab.placeholder');
    }

    public function render()
    {
        return view('livewire.dashboard.tab.overview');
    }
}
