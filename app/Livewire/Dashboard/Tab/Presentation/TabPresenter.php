<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Models\DMS;
use App\Models\Event;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;

class TabPresenter
{
    public function stats(): array
    {
        $uid = auth()->id();

        return Cache::remember("home_stats_{$uid}", now()->addSeconds(60), fn(): array => [
            [
                'key' => 'documents',
                'icon' => 'verified',
                'label' => 'اسناد در انتظار من',
                'value' => DMS::getUnsignedDocumentsCount(),
                'nav' => ['type' => 'route', 'name' => 'dms'],
            ],
            [
                'key' => 'tickets',
                'icon' => 'support_agent',
                'label' => 'تیکت‌های باز من',
                'value' => Ticket::getOpenTicketCount() + Ticket::getInProgressTicketCount(),
                'nav' => ['type' => 'route', 'name' => 'ths'],
            ],
            [
                'key' => 'tasks',
                'icon' => 'checklist',
                'label' => 'وظایف باز من',
                'value' => Task::getTodoCount($uid) + Task::getInProgressCount($uid) + Task::getPendingCount($uid),
                'nav' => ['type' => 'route', 'name' => 'tasks'],
            ],
            [
                'key' => 'events',
                'icon' => 'event_upcoming',
                'label' => 'رویدادهای هفته پیش‌رو',
                'value' => Event::whereBetween('date', [now(), now()->addWeek()])->where(fn($q) => $q->where('user_id', $uid)->orWhere('private', false)->orWhereHas('shares', fn($sq) => $sq->where('user_id', $uid)))->count(),
                'nav' => ['type' => 'tab', 'tab' => 'calendar'],
            ],
        ]);
    }

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

    public function shortcuts(): array
    {
        $tab   = fn (string $key, string $title, string $icon, string $target) =>
        ['key' => $key, 'title' => $title, 'icon' => $icon, 'type' => 'tab',   'target' => $target, 'url' => null];
        $route = fn (string $key, string $title, string $icon, string $name) =>
        ['key' => $key, 'title' => $title, 'icon' => $icon, 'type' => 'route', 'target' => $name,   'url' => route($name)];

        return [
            $tab('home',     'خانه',              'home',            'home'),
            $tab('post',     'اعلانات',           'campaign',        'post'),
            $tab('feed',     'اخبار و فیدها',     'rss_feed',        'feed'),
            $tab('calendar', 'تقویم کاری',        'calendar_month',  'calendar'),
            $tab('gallery',  'گالری تصاویر',      'photo_library',   'gallery'),
            $tab('reports',  'گزارشات',           'show_chart',      'reports'),
            $tab('links',    'لینک ها', 'open_in_new',     'links'),
            $tab('status',   'وضعیت همکاران',     'badge',           'status'),
            $tab('faqs',     'پرسش‌های متداول',   'help',            'faqs'),
            $route('dms',          'مدیریت اسناد',     'folder_managed',         'dms'),
            $route('ths',          'سیستم تیکت',       'support_agent',          'ths'),
            $route('tasks',        'تسک بورد',         'view_kanban',            'tasks'),
            $route('reservation',  'رزرو فضا و منابع', 'meeting_room',           'reservation'),
            $route('ads',          'فرصت‌های شغلی',    'work',                   'ads'),
            $route('suggestion',   'پیشنهادات',        'lightbulb',              'suggestion'),
            $route('authority',    'اختیارات',         'verified_user',          'authority'),
            $route('contact',      'پیام‌رسان داخلی',  'perm_contact_calendar',  'contact'),
            $route('energy',       'پرسش‌نامه انرژی',  'electric_bolt',          'energy'),
            ['key' => 'profile', 'title' => 'پروفایل من', 'icon' => 'person', 'type' => 'url', 'target' => 'profile', 'url' => route('profile')],
        ];
    }
}
