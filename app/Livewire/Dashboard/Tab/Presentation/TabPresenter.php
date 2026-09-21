<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Enums\PresenceStatus;
use App\Models\ChannelMessage;
use App\Models\DMS;
use App\Models\Event;
use App\Models\Message;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TabPresenter
{
    public function teamPulse(): Collection
    {
        return $this->rankedOnlineUsers()->reject(fn(User $u) => $u->id === auth()->id())->values();
    }

    public function rankedOnlineUsers(): Collection
    {
        return ModelCacheVersion::rememberGlobal('home_team_pulse', fn(): Collection =>
            User::active()
                ->whereIn('presence', [PresenceStatus::Onsite->value, PresenceStatus::Busy->value])
                ->with(['profile', 'profile.details' => fn($q) => $q->where('key', 'display_name')])
                ->get()
                ->sortBy(fn(User $u) => $u->rank())
                ->values(),
            now()->addSeconds(60)
        );
    }

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
            [
                'key' => 'messages',
                'icon' => 'chat_bubble',
                'label' => 'پیام‌های خوانده‌نشده',
                'value' => Message::totalUnreadFor($uid) + ChannelMessage::totalUnreadFor($uid),
                'nav' => ['type' => 'route', 'name' => 'contact'],
            ],
            [
                'key' => 'reminders',
                'icon' => 'alarm',
                'label' => 'یادآورهای سررسید',
                'value' => Reminder::dueBadgeCount($uid),
                'nav' => ['type' => 'event', 'name' => 'open-reminders'],
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
        $shortcuts = [
            ['key' => 'home', 'title' => 'خانه', 'icon' => 'home', 'type' => 'tab', 'target' => 'home', 'url' => null],
        ];

        foreach (config('modules', []) as $module) {
            $nav = $module['nav'] ?? null;

            if ($nav === null) {
                continue;
            }

            $shortcuts[] = [
                'key'    => $nav['key'] ?? $module['id'],
                'title'  => $nav['title'] ?? trim($module['title']),
                'icon'   => $nav['icon'] ?? $module['icon'],
                'type'   => $nav['type'],
                'target' => $nav['target'],
                'url'    => $nav['type'] === 'tab' ? null : route($nav['target']),
            ];
        }

        return $shortcuts;
    }

    public function heroGadgetCatalog(): array
    {
        return collect($this->shortcuts())
            ->reject(fn (array $s) => $s['key'] === 'home')
            ->map(fn (array $s) => [
                'title' => $s['title'],
                'icon' => $s['icon'],
                'src' => $this->embedSrc($s),
            ])
            ->values()
            ->all();
    }

    private function embedSrc(array $s): string
    {
        $url = $s['url'] ?? route('dashboard', ['tab' => $s['target']]);

        return $url . (str_contains($url, '?') ? '&' : '?') . 'embed=1';
    }
}
