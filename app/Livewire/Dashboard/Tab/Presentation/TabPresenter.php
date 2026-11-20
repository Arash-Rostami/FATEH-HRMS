<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Enums\PresenceStatus;
use App\Livewire\Dashboard\Messaging\Actions\FetchMessagingUnreadAction;
use App\Models\ChannelMessage;
use App\Models\Department;
use App\Models\DMS;
use App\Models\Event;
use App\Models\Launchpad;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Menu\StateService;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TabPresenter
{
    public const GADGET_GROUPS = [
        'module' => ['label' => 'ماژول‌های سیستم', 'tab' => 'ماژول‌ها', 'icon' => 'widgets'],
        'program' => ['label' => 'برنامه‌های آماده', 'tab' => 'برنامه‌ها', 'icon' => 'deployed_code'],
        'sso' => ['label' => 'سامانه‌های متصل', 'tab' => 'سامانه‌ها', 'icon' => 'hub'],
    ];

    public static function statStamp(int $userId): string
    {
        return implode('.', [
            StateService::version(),
            ModelCacheVersion::version(ChannelMessage::class),
            ModelCacheVersion::version(Reminder::class),
            ModelCacheVersion::version(Event::class),
            ModelCacheVersion::version("channel-read:{$userId}"),
        ]);
    }

    private static function statsKey(): string
    {
        return 'home_stats_' . auth()->id() . ':' . self::statStamp(auth()->id());
    }

    public static function forgetStats(): void
    {
        Cache::forget(self::statsKey());
    }

    public static function forgetTeamPulse(): void
    {
        if (Cache::add('home_team_pulse_forgot', true, 5)) {
            Cache::forget('home_team_pulse');
        }
    }

    public function teamPulse(): Collection
    {
        return $this->rankedOnlineUsers()->reject(fn(User $u) => $u->id === auth()->id())->values();
    }

    public function rankedOnlineUsers(): Collection
    {
        return ModelCacheVersion::rememberGlobal('home_team_pulse', fn(): Collection =>
            User::visibleOnBoard()
                ->online()
                ->whereNotIn('presence', PresenceStatus::unreachableValues())
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

        return Cache::remember(self::statsKey(), now()->addSeconds(60), fn(): array => [
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
                'value' => array_sum(app(FetchMessagingUnreadAction::class)->execute($uid)),
                'nav' => ['type' => 'route', 'name' => tenantOff('contact') ? 'channels' : 'contact'],
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

            if ($nav === null || tenantOff($module['id'])) {
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
                'group' => 'module',
                'badge' => null,
                'subtitle' => null,
                'new' => false,
                'expiring' => false,
            ])
            ->concat($this->launchpadOptions())
            ->sortBy(fn (array $item) => array_search($item['group'], array_keys(self::GADGET_GROUPS), true))
            ->values()
            ->all();
    }

    private function launchpadOptions(): Collection
    {
        if (tenantOff('launchpad')) {
            return collect();
        }

        $user = auth()->user();
        $ids = Launchpad::optionIds($user);
        if ($ids === []) {
            return collect();
        }

        $departments = Department::getCachedOptions();
        $ssoSites = Launchpad::ssoSites();

        return Launchpad::query()
            ->whereIn('id', $ids)
            ->get(['id', 'user_id', 'department_id', 'title', 'kind', 'settings', 'active', 'audience', 'seen_by', 'accessible_from', 'accessible_to', 'expire_at'])
            ->filter(fn (Launchpad $row) => $row->kind === 'static' || array_key_exists($row->settings['site'] ?? '', $ssoSites))
            ->filter(fn (Launchpad $row) => LaunchpadAccessPolicy::workspaceBlock($row, $user) === null)
            ->map(function (Launchpad $row) use ($departments, $ssoSites, $user) {
                $site = $row->kind === 'static' ? null : $ssoSites[$row->settings['site']];

                return [
                    'title' => $row->title,
                    'icon' => $site ? 'hub' : 'deployed_code',
                    'src' => route('launchpad', ['a' => $row->id, 'embed' => 1]),
                    'group' => $site ? 'sso' : 'program',
                    'badge' => $site ? $site['title'] : $departments->get($row->department_id),
                    'subtitle' => $site ? parse_url((string) $site['url'], PHP_URL_HOST) : $departments->get($row->department_id),
                    'new' => $row->isUnseenBy((int) $user->id),
                    'expiring' => $row->status() === 'soon',
                ];
            })
            ->values();
    }

    public function bottomNavPaging(array $tabs, string $activeTab): array
    {
        $perPage = max((int) ceil(count($tabs) / 2), 4);
        $chunks = array_chunk($tabs, $perPage, true);
        $pageOne = $chunks[0] ?? [];
        $pageTwo = $chunks[1] ?? [];
        $pageTwoKeys = array_keys($pageTwo);
        $initialPage = in_array($activeTab, $pageTwoKeys) ? 1 : 0;

        return compact('perPage', 'pageOne', 'pageTwo', 'pageTwoKeys', 'initialPage');
    }

    private function embedSrc(array $s): string
    {
        $url = $s['url'] ?? route('dashboard', ['tab' => $s['target']]);

        return $url . (str_contains($url, '?') ? '&' : '?') . 'embed=1';
    }
}
