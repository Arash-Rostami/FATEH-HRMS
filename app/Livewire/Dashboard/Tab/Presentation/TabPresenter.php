<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

class TabPresenter
{
    public function tools(): array
    {
        return [
            [
                'title' => 'پروفایل من',
                'icon' => 'person',
                'action' => 'profile',
                'color' => 'var(--tool-amethyst-color)',
                'bg' => 'var(--tool-amethyst-bg)',
                'text' => 'var(--tool-amethyst-text)',
            ],
            [
                'title' => 'تقویم کاری',
                'icon' => 'calendar_month',
                'action' => 'calendar',
                'color' => 'var(--tool-sapphire-color)',
                'bg' => 'var(--tool-sapphire-bg)',
                'text' => 'var(--tool-sapphire-text)',
            ],
            [
                'title' => 'گزارشات',
                'icon' => 'show_chart',
                'action' => 'reports',
                'color' => 'var(--tool-sage-color)',
                'bg' => 'var(--tool-sage-bg)',
                'text' => 'var(--tool-sage-text)',
            ],
            [
                'title' => 'وضعیت همکاران',
                'icon' => 'group',
                'action' => 'status',
                'color' => 'var(--tool-gold-color)',
                'bg' => 'var(--tool-gold-bg)',
                'text' => 'var(--tool-gold-text)',
            ],
        ];
    }
}
