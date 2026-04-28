<?php

namespace App\Filament\Widgets;

use App\Models\Ad;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class ModuleAnalytics extends Widget implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'livewire.admin.widgets.filament-analytics';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public string $activeTab = 'users';

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
            'users'       => count($this->usersData),
            'departments' => count($this->departmentsData),
            'ads'         => count($this->adsData),
            'reports'     => count($this->reportsData),
            default       => 0,
        };
    }

    public function statsSchema(Schema $schema): Schema
    {
        $stats = match ($this->activeTab) {
            'users'       => $this->usersData,
            'departments' => $this->departmentsData,
            'ads'         => $this->adsData,
            'reports'     => $this->reportsData,
            default       => [],
        };

        return $schema->components([
            Grid::make()
                ->schema($stats)
                ->columns([
                    'default' => 1,
                    'sm'      => 2,
                    'md'      => 3,
                    'xl'      => 4,
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
    public function departmentsData(): array
    {
        $departments = Department::withCount('users')->get();

        $total     = $departments->count();
        $withUsers = $departments->filter(fn ($d) => $d->users_count > 0)->count();
        $mostDense = $departments->sortByDesc('users_count')->first()?->name ?? 'نامشخص';

        return [
            Stat::make('کل دپارتمان‌ها', $total)
                ->icon('heroicon-o-building-office')
                ->color('primary'),
            Stat::make('دارای کاربر', $withUsers)
                ->icon('heroicon-o-user-group')
                ->color('success'),
            Stat::make('پرتراکم‌ترین دپارتمان', $mostDense)
                ->icon('heroicon-o-chart-bar')
                ->color('warning'),
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
    public function reportsData(): array
    {
        $stats = DB::table('reports')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active')
            ->first();

        $mostDense = Department::withCount('reports')->get()->sortByDesc('reports_count')->first()?->description ?? 'نامشخص';


        return [
            Stat::make('دپارتمان با بیشترین گزارش', $mostDense)
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
