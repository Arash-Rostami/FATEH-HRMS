<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;

class ModuleAnalytics extends Widget implements HasSchemas
{
    use InteractsWithSchemas;

    protected static bool $isLazy = true;
    protected static ?int $sort = 3;
    public string $activeTab = 'users';
    protected string $view = 'livewire.admin.widgets.filament-analytics';
    protected int|string|array $columnSpan = 'full';

    #[Computed(seconds: 300, cache: true)]
    public function adsData(): array
    {
        $stats = DB::table('ads')->selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN active = 1 THEN 1 END) as active,
            COUNT(CASE WHEN gender = 'Male' THEN 1 END) as males,
            COUNT(CASE WHEN gender = 'Female' THEN 1 END) as females,
            COUNT(CASE WHEN gender = 'Any' OR gender IS NULL THEN 1 END) as both_gender
        ")->first();

        return [
            Stat::make('کل آگهی‌ها', $stats->total)
                ->icon('heroicon-o-megaphone')
                ->color('primary'),
            Stat::make('آگهی‌های فعال', $stats->active)
                ->icon('heroicon-o-check')
                ->color('success'),
            Stat::make('آقایان / خانم‌ها', "{$stats->males} ┆ {$stats->females}")
                ->icon('heroicon-o-user')
                ->color('info'),
            Stat::make('بدون محدودیت جنسیت', $stats->both_gender)
                ->icon('heroicon-o-users')
                ->color('gray'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function authoritiesData(): array
    {
        $stats = DB::table('authorities')->selectRaw("
            COUNT(*) as total,
            COUNT(DISTINCT department_id) as depts_with_authorities,
            SUM(CASE WHEN sub_duty = 1 THEN 1 ELSE 0 END) as sub_duties
        ")->first();

        return [
            Stat::make(__('resources/authority/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-shield-check')
                ->color('primary'),
            Stat::make(__('resources/department/strings.plural_label'), $stats->depts_with_authorities)
                ->icon('heroicon-o-building-office')
                ->color('info'),
            Stat::make(__('resources/authority/strings.fields.sub_duty'), $stats->sub_duties)
                ->icon('heroicon-o-queue-list')
                ->color('gray'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function contactsData(): array
    {
        $stats = DB::table('messages')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread,
            SUM(CASE WHEN reply_to_id IS NOT NULL THEN 1 ELSE 0 END) as replies
        ")->whereNull('deleted_at')->first();


        return [
            Stat::make(__('resources/contact/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('primary'),
            Stat::make(__('resources/contact/strings.fields.unread'), $stats->unread)
                ->icon('heroicon-o-envelope')
                ->color('warning'),
            Stat::make(__('resources/contact/strings.fields.has_reply'), $stats->replies)
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('info'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function credentialsData(): array
    {
        $stats = DB::table('credentials')->selectRaw("
            COUNT(*) as total,
            COUNT(DISTINCT app_name) as apps,
            SUM(CASE WHEN link IS NOT NULL THEN 1 ELSE 0 END) as with_links
        ")->first();

        return [
            Stat::make(__('resources/credential/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-key')
                ->color('primary'),
            Stat::make(__('resources/credential/strings.fields.app_name'), $stats->apps)
                ->icon('heroicon-o-computer-desktop')
                ->color('info'),
            Stat::make(__('resources/credential/strings.filters.has_link'), $stats->with_links)
                ->icon('heroicon-o-link')
                ->color('gray'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function departmentsData(): array
    {
        $departments = Cache::remember('module_analytics_departments', 300, function () {
            return Department::withCount('users')->get();
        });

        $total = $departments->count();
        $withUsers = $departments->filter(fn($d) => $d->users_count > 0)->count();
        $mostDense = $departments->sortByDesc('users_count')->first()?->description ?? 'نامشخص';

        return [
            Stat::make('کل واحدها', $total)
                ->icon('heroicon-o-building-office')
                ->color('primary'),
            Stat::make('دارای کاربر', $withUsers)
                ->icon('heroicon-o-user-group')
                ->color('success'),
            Stat::make(
                'پرتراکم‌ترین واحدها',
                new HtmlString("
                            <div class='text-[14px] sm:text-md font-medium tracking-tighter flex items-center gap-1'>
                                 <span>{$mostDense}</span>
                            </div>
                    "))
                ->icon('heroicon-o-chart-bar')
                ->color('warning'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function dmsData(): array
    {
        $stats = DB::table('dms')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'live' THEN 1 ELSE 0 END) as live,
            SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
            SUM(combined_read_count) as total_reads
        ")->first();

        return [
            Stat::make(__('resources/dms/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-document-duplicate')
                ->color('primary'),
            Stat::make(__('resources/dms/strings.enums.status.live'), $stats->live)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('resources/dms/strings.enums.status.under_review'), $stats->under_review)
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make(__('resources/dms/strings.fields.read_count'), $stats->total_reads ?? 0)
                ->icon('heroicon-o-eye')
                ->color('info'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function energyData(): array
    {
        $stats = DB::table('energy_tests')
            ->selectRaw("
            COUNT(*) AS total,
            COALESCE(ROUND(AVG(overall_score), 1), 0) AS avg_overall,
            COALESCE(ROUND(AVG(mind_score), 1), 0) AS avg_mind,
            COALESCE(ROUND(AVG(emotion_score), 1), 0) AS avg_emotion,
            COALESCE(ROUND(AVG(physique_score), 1), 0) AS avg_physique,
            COALESCE(ROUND(AVG(soul_score), 1), 0) AS avg_soul,
            SUM(CASE WHEN overall_score >= 12 THEN 1 ELSE 0 END) AS high_risk_count,
            SUM(CASE WHEN completed_at >= ? THEN 1 ELSE 0 END) AS last_month_count
        ", [now()->subDays(30)])
            ->first();

        return [
            Stat::make(__('resources/energy/strings.stats.total'), $stats->total)
                ->icon('heroicon-o-bolt')
                ->color('primary'),

            Stat::make(__('resources/energy/strings.stats.avg_overall'), $stats->avg_overall)
                ->icon('heroicon-o-chart-bar')
                ->color($stats->avg_overall >= 12 ? 'danger' : ($stats->avg_overall > 8 ? 'warning' : 'success')),

            Stat::make(
                __('resources/energy/strings.stats.dimension_averages'),
                new HtmlString("
               <div class='text-[14px] sm:text-md font-medium tracking-tighter flex items-center gap-1'>
                    <span>🏋️ {$stats->avg_physique}</span>
                    <span class='text-gray-300'></span>
                    <span>❤️ {$stats->avg_emotion}</span>
                    <span class='text-gray-300'>|</span>
                    <span>🧠 {$stats->avg_mind}</span>
                    <span class='text-gray-300'>|</span>
                    <span>✨ {$stats->avg_soul}</span>
                </div>
            ")
            )
                ->icon('heroicon-o-squares-2x2')
                ->color('info'),

            Stat::make(__('resources/energy/strings.stats.low_scores'), $stats->high_risk_count)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($stats->high_risk_count > 0 ? 'danger' : 'success')
                ->description(__('resources/energy/strings.stats.above_12')),

            Stat::make(__('resources/energy/strings.stats.last_month'), $stats->last_month_count)
                ->icon('heroicon-o-calendar')
                ->color('gray'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function eventsData(): array
    {
        $stats = DB::table('events')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN date >= CURRENT_DATE THEN 1 ELSE 0 END) as upcoming,
            SUM(CASE WHEN private = 1 THEN 1 ELSE 0 END) as private
        ")->first();

        return [
            Stat::make(__('resources/event/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),
            Stat::make(__('resources/event/strings.filters.upcoming'), $stats->upcoming)
                ->icon('heroicon-o-clock')
                ->color('success'),
            Stat::make(__('resources/event/strings.fields.private'), $stats->private)
                ->icon('heroicon-o-lock-closed')
                ->color('warning'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function faqsData(): array
    {
        $stats = DB::table('faqs')->selectRaw("
            COUNT(*) as total,
            COUNT(DISTINCT category) as categories,
            COUNT(DISTINCT department_id) as depts
        ")->first();

        return [
            Stat::make(__('resources/faq/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-question-mark-circle')
                ->color('primary'),
            Stat::make(__('resources/faq/strings.fields.category'), $stats->categories)
                ->icon('heroicon-o-tag')
                ->color('info'),
            Stat::make(__('resources/department/strings.plural_label'), $stats->depts)
                ->icon('heroicon-o-building-office')
                ->color('gray'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function feedsData(): array
    {
        $stats = DB::table('feeds')->selectRaw("
            COUNT(*) as total,
            COUNT(DISTINCT category) as categories,
            SUM(CASE WHEN media_paths IS NOT NULL AND JSON_LENGTH(media_paths) > 0 THEN 1 ELSE 0 END) as with_media
        ")->first();

        $reactionsCount = DB::table('reactions')->count();

        return [
            Stat::make(__('resources/feed/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-rss')
                ->color('primary'),
            Stat::make(__('resources/feed/strings.filters.has_media'), $stats->with_media)
                ->icon('heroicon-o-photo')
                ->color('info'),
            Stat::make(__('resources/feed/strings.fields.reactions_count'), $reactionsCount)
                ->icon('heroicon-o-heart')
                ->color('danger'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function galleriesData(): array
    {
        $stats = DB::table('photos')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN department_id IS NULL THEN 1 ELSE 0 END) as public_galleries,
            SUM(JSON_LENGTH(path)) as total_photos
        ")->first();

        return [
            Stat::make(__('resources/gallery/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-photo')
                ->color('primary'),
            Stat::make(__('resources/gallery/strings.fields.public_gallery'), $stats->public_galleries)
                ->icon('heroicon-o-globe-alt')
                ->color('info'),
            Stat::make(__('resources/gallery/strings.fields.count'), $stats->total_photos ?? 0)
                ->icon('heroicon-o-camera')
                ->color('success'),
        ];
    }

    public function getActiveStatsCount(): int
    {
        return match ($this->activeTab) {
            'users' => count($this->usersData),
            'departments' => count($this->departmentsData),
            'ads' => count($this->adsData),
            'reports' => count($this->reportsData),
            'energy' => count($this->energyData),
            'authorities' => count($this->authoritiesData),
            'contacts' => count($this->contactsData),
            'credentials' => count($this->credentialsData),
            'dms' => count($this->dmsData),
            'events' => count($this->eventsData),
            'faqs' => count($this->faqsData),
            'feeds' => count($this->feedsData),
            'galleries' => count($this->galleriesData),
            'links' => count($this->linksData),
            'onboardings' => count($this->onboardingsData),
            'posts' => count($this->postsData),
            'profiles' => count($this->profilesData),
            'suggestions' => count($this->suggestionsData),
            'tasks' => count($this->tasksData),
            'ths' => count($this->thsData),
            default => 0,
        };
    }

    #[Computed]
    public function getAllTabs(): array
    {
        return [
            'users' => ['icon' => 'heroicon-o-users', 'label' => __('کاربران')],
            'profiles' => ['icon' => 'heroicon-o-identification', 'label' => __('resources/profile/strings.navigation.plural')],
            'departments' => ['icon' => 'heroicon-o-building-office', 'label' => __('resources/department/strings.plural_label')],
            'authorities' => ['icon' => 'heroicon-o-shield-check', 'label' => __('resources/authority/strings.plural_label')],
            'credentials' => ['icon' => 'heroicon-o-key', 'label' => __('resources/credential/strings.plural_label')],
            'ads' => ['icon' => 'heroicon-o-megaphone', 'label' => __('resources/ad/strings.plural_label')],
            'posts' => ['icon' => 'heroicon-o-document-text', 'label' => __('resources/post/strings.plural_label')],
            'reports' => ['icon' => 'heroicon-o-document-text', 'label' => __('resources/report/strings.plural_label')],
            'events' => ['icon' => 'heroicon-o-calendar-days', 'label' => __('resources/event/strings.plural_label')],
            'suggestions' => ['icon' => 'heroicon-o-light-bulb', 'label' => __('resources/suggestion/strings.plural_label')],
            'tasks' => ['icon' => 'heroicon-o-clipboard-document-list', 'label' => __('resources/task/strings.plural_label')],
            'dms' => ['icon' => 'heroicon-o-document-duplicate', 'label' => __('resources/dms/strings.plural_label')],
            'energy' => ['icon' => 'heroicon-o-bolt', 'label' => __('resources/energy/strings.label')],
            'contacts' => ['icon' => 'heroicon-o-chat-bubble-left-ellipsis', 'label' => __('resources/contact/strings.plural_label')],
            'feeds' => ['icon' => 'heroicon-o-rss', 'label' => __('resources/feed/strings.plural_label')],
            'galleries' => ['icon' => 'heroicon-o-photo', 'label' => __('resources/gallery/strings.plural_label')],
            'links' => ['icon' => 'heroicon-o-link', 'label' => __('resources/link/strings.plural_label')],
            'onboardings' => ['icon' => 'heroicon-o-academic-cap', 'label' => __('resources/onboarding/strings.plural_label')],
            'faqs' => ['icon' => 'heroicon-o-question-mark-circle', 'label' => __('resources/faq/strings.plural_label')],
            'ths' => ['icon' => 'heroicon-o-ticket', 'label' => __('resources/ths/strings.plural_label')],
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function linksData(): array
    {
        $stats = DB::table('links')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN link = 'internal' THEN 1 ELSE 0 END) as internal,
            SUM(CASE WHEN link = 'external' THEN 1 ELSE 0 END) as external,
            SUM(CASE WHEN extra IS NOT NULL AND JSON_LENGTH(extra) > 0 THEN 1 ELSE 0 END) as smart_routing
        ")->first();

        return [
            Stat::make(__('resources/link/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-link')
                ->color('primary'),
            Stat::make(__('resources/link/strings.fields.internal_url'), $stats->internal)
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info'),
            Stat::make(__('resources/link/strings.fields.url'), $stats->external)
                ->icon('heroicon-o-arrow-up-right')
                ->color('warning'),
            Stat::make(__('resources/link/strings.fields.smart_routing'), $stats->smart_routing)
                ->icon('heroicon-o-cpu-chip')
                ->color('success'),
        ];
    }

    public function mount(): void
    {
        $this->activeTab = 'users';
    }

    #[Computed(seconds: 300, cache: true)]
    public function onboardingsData(): array
    {
        $stats = DB::table('onboardings')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as global_audience
        ")->first();

        return [
            Stat::make(__('resources/onboarding/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),
            Stat::make(__('resources/onboarding/strings.fields.is_active'), $stats->active)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('resources/onboarding/strings.fields.global_audience'), $stats->global_audience)
                ->icon('heroicon-o-globe-alt')
                ->color('info'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function postsData(): array
    {
        $stats = DB::table('posts')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN pinned = 1 THEN 1 ELSE 0 END) as pinned,
            SUM(CASE WHEN image IS NOT NULL THEN 1 ELSE 0 END) as with_image
        ")->first();

        return [
            Stat::make(__('resources/post/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            Stat::make(__('resources/post/strings.fields.pinned'), $stats->pinned)
                ->icon('heroicon-o-paper-clip')
                ->color('warning'),
            Stat::make(__('resources/post/strings.filters.has_image'), $stats->with_image)
                ->icon('heroicon-o-photo')
                ->color('info'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function profilesData(): array
    {
        $stats = DB::table('profiles')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN employment_status = 'active' THEN 1 ELSE 0 END) as active_employees,
            SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as females,
            SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as males
        ")->first();

        return [
            Stat::make(__('resources/profile/strings.navigation.plural'), $stats->total)
                ->icon('heroicon-o-identification')
                ->color('primary'),
            Stat::make(__('resources/profile/strings.table.employment_status'), $stats->active_employees)
                ->icon('heroicon-o-check-badge')
                ->color('success'),
            Stat::make(__('resources/ad/strings.gender.any'), $stats->males . ' ┆ ' . $stats->females)
                ->icon('heroicon-o-users')
                ->color('info'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function reportsData(): array
    {
        $stats = DB::table('reports')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active')
            ->first();

        $mostDense = Department::withCount('reports')->orderByDesc('reports_count')->first()?->description ?? 'نامشخص';

        return [
            Stat::make('واحدها با بیشترین گزارش', $mostDense)
                ->icon('heroicon-o-chart-bar')
                ->extraAttributes(['class' => '!text-xs'])
                ->color('warning'),
            Stat::make('کل گزارش‌ها', $stats->total)
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            Stat::make('گزارش‌های فعال', $stats->active)
                ->icon('heroicon-o-document-check')
                ->color('success'),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function statsSchema(Schema $schema): Schema
    {
        $stats = match ($this->activeTab) {
            'users' => $this->usersData,
            'departments' => $this->departmentsData,
            'ads' => $this->adsData,
            'reports' => $this->reportsData,
            'energy' => $this->energyData,
            'authorities' => $this->authoritiesData,
            'contacts' => $this->contactsData,
            'credentials' => $this->credentialsData,
            'dms' => $this->dmsData,
            'events' => $this->eventsData,
            'faqs' => $this->faqsData,
            'feeds' => $this->feedsData,
            'galleries' => $this->galleriesData,
            'links' => $this->linksData,
            'onboardings' => $this->onboardingsData,
            'posts' => $this->postsData,
            'profiles' => $this->profilesData,
            'suggestions' => $this->suggestionsData,
            'tasks' => $this->tasksData,
            'ths' => $this->thsData,
            default => [],
        };

        return $schema->components([
            Grid::make()
                ->schema($stats)
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                    'lg' => 4,
                    '2xl' => 5,
                ]),
        ]);
    }

    #[Computed(seconds: 300, cache: true)]
    public function suggestionsData(): array
    {
        $stats = DB::table('suggestions')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN stage IN ('pending', 'team_remarks', 'dept_remarks', 'awaiting_decision', 'under_review') THEN 1 ELSE 0 END) as in_process,
            SUM(CASE WHEN stage = 'awaiting_decision' THEN 1 ELSE 0 END) as awaiting_decision,
            SUM(CASE WHEN stage = 'accepted' THEN 1 ELSE 0 END) as accepted,
            SUM(CASE WHEN stage = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN self_fill = 1 THEN 1 ELSE 0 END) as self_filled,
            SUM(CASE WHEN attachment IS NOT NULL THEN 1 ELSE 0 END) as with_attachment
        ")->first();

        $referralCount = DB::table('reviews')
            ->where('department_id', 'MA')
            ->whereNotNull('referral')
            ->count();

        return [
            Stat::make(__('resources/suggestion/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-light-bulb')
                ->color('primary'),
            Stat::make(__('resources/suggestion/strings.tabs.awaiting_decision'), $stats->awaiting_decision)
                ->icon('heroicon-o-scale')
                ->color('warning'),
            Stat::make(__('resources/suggestion/strings.tabs.accepted'), $stats->accepted)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('resources/suggestion/strings.tabs.rejected'), $stats->rejected)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make(__('resources/suggestion/strings.filters.has_referral'), $referralCount)
                ->icon('heroicon-o-arrow-uturn-right')
                ->color('info'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function tasksData(): array
    {
        $stats = DB::table('tasks')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo,
            SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN deadline < NOW() AND status != 'done' THEN 1 ELSE 0 END) as overdue
        ")->whereNull('deleted_at')->first();

        return [
            Stat::make(__('resources/task/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),
            Stat::make(__('resources/task/strings.tabs.in_progress'), $stats->in_progress)
                ->icon('heroicon-o-arrow-path')
                ->color('info'),
            Stat::make(__('resources/task/strings.tabs.todo'), $stats->todo)
                ->icon('heroicon-o-clock')
                ->color('gray'),
            Stat::make(__('resources/task/strings.tabs.overdue'), $stats->overdue)
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function thsData(): array
    {
        $stats = DB::table('tickets')->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
            SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned
        ")->first();

        return [
            Stat::make(__('resources/ths/strings.plural_label'), $stats->total)
                ->icon('heroicon-o-ticket')
                ->color('primary'),
            Stat::make(__('resources/ths/strings.tabs.open'), $stats->open_tickets)
                ->icon('heroicon-o-envelope-open')
                ->color('danger'),
            Stat::make(__('resources/ths/strings.tabs.in_progress'), $stats->in_progress)
                ->icon('heroicon-o-arrow-path')
                ->color('info'),
            Stat::make(__('resources/ths/strings.tabs.unassigned'), $stats->unassigned)
                ->icon('heroicon-o-user-minus')
                ->color('warning'),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function usersData(): array
    {
        $stats = DB::table('users')->selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
            COUNT(CASE WHEN role IN ('admin', 'developer') THEN 1 END) as admins,
            COUNT(CASE WHEN last_seen >= ? OR presence = 'onsite' THEN 1 END) as online
        ", [now()->subMinutes(15)])->first();

        return [
            Stat::make('کل کاربران', $stats->total)
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('کاربران فعال', $stats->active)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('ادمین‌ها', $stats->admins)
                ->icon('heroicon-o-shield-check')
                ->color('danger'),
            Stat::make('کاربران آنلاین', $stats->online)
                ->icon('heroicon-o-signal')
                ->color('info'),
        ];
    }
}
