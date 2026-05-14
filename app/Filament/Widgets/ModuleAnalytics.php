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
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;

class ModuleAnalytics extends Widget implements HasSchemas
{
    use InteractsWithSchemas;

    protected static ?int $sort = 3;
    public string $activeTab = 'users';
    protected string $view = 'livewire.admin.widgets.filament-analytics';
    protected int|string|array $columnSpan = 'full';


    public function mount(): void
    {
        $this->activeTab = 'users';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
    public function getActiveStatsCount(): int
    {
        return match ($this->activeTab) {
            'users' => count($this->usersData),
            'departments' => count($this->departmentsData),
            'ads' => count($this->adsData),
            'reports' => count($this->reportsData),
            'energy' => count($this->energyData),
            default => 0,
        };
    }

    public function statsSchema(Schema $schema): Schema
    {
        $stats = match ($this->activeTab) {
            'users' => $this->usersData,
            'departments' => $this->departmentsData,
            'ads' => $this->adsData,
            'reports' => $this->reportsData,
            'energy' => $this->energyData,
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
    public function departmentsData(): array
    {
        $departments = Department::withCount('users')->get();

        $total = $departments->count();
        $withUsers = $departments->filter(fn($d) => $d->users_count > 0)->count();
        $mostDense = $departments->sortByDesc('users_count')->first()?->name ?? 'نامشخص';

        return [
            Stat::make('کل واحدها', $total)
                ->icon('heroicon-o-building-office')
                ->color('primary'),
            Stat::make('دارای کاربر', $withUsers)
                ->icon('heroicon-o-user-group')
                ->color('success'),
            Stat::make('پرتراکم‌ترین واحدها', $mostDense)
                ->icon('heroicon-o-chart-bar')
                ->color('warning'),
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
    public function reportsData(): array
    {
        $stats = DB::table('reports')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active')
            ->first();

        $mostDense = Department::withCount('reports')->get()->sortByDesc('reports_count')->first()?->description ?? 'نامشخص';

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
}
