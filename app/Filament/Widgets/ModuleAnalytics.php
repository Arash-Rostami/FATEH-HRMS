<?php

namespace App\Filament\Widgets;

use App\Models\Ad;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Filament\Schemas\Schema;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Grid;

class ModuleAnalytics extends Widget implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'components.admin.widgets.analytics';

    protected static ?int $sort = 10;

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

    public function statsSchema(Schema $schema): Schema
    {
        $stats = match($this->activeTab) {
            'users' => $this->usersData,
            'departments' => $this->departmentsData,
            'ads' => $this->adsData,
            'reports' => $this->reportsData,
            default => [],
        };

        return $schema->components([
            Grid::make()
                ->schema($stats)
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                    'xl' => 4,
                ])
        ]);
    }

    #[Computed]
    public function usersData(): array
    {
        return [
            Stat::make('کل کاربران', User::count())
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('کاربران فعال', User::active()->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('ادمین‌ها', User::whereIn('role', ['admin', 'developer'])->count())
                ->icon('heroicon-o-shield-check')
                ->color('danger'),
            Stat::make('کاربران آنلاین', User::online()->count())
                ->icon('heroicon-o-signal')
                ->color('info'),
        ];
    }

    #[Computed]
    public function departmentsData(): array
    {
        return [
            Stat::make('کل دپارتمان‌ها', Department::count())
                ->icon('heroicon-o-building-office')
                ->color('primary'),
            Stat::make('دارای کاربر', Department::has('users')->count())
                ->icon('heroicon-o-user-group')
                ->color('success'),
            Stat::make('پرتراکم‌ترین دپارتمان', Department::withCount('users')->orderByDesc('users_count')->first()?->name ?? 'نامشخص')
                ->icon('heroicon-o-chart-bar')
                ->color('warning'),
        ];
    }

    #[Computed]
    public function adsData(): array
    {
        return [
            Stat::make('کل آگهی‌ها', Ad::count())
                ->icon('heroicon-o-megaphone')
                ->color('primary'),
            Stat::make('آگهی‌های فعال', Ad::countActiveJobs())
                ->icon('heroicon-o-check')
                ->color('success'),
            Stat::make('آقایان / خانم‌ها', Ad::countMales() . ' / ' . Ad::countFemales())
                ->icon('heroicon-o-user')
                ->color('info'),
            Stat::make('بدون محدودیت جنسیت', Ad::countBothGender())
                ->icon('heroicon-o-users')
                ->color('gray'),
        ];
    }

    #[Computed]
    public function reportsData(): array
    {
        return [
            Stat::make('کل گزارش‌ها', Report::count())
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            Stat::make('گزارش‌های فعال', Report::active()->count())
                ->icon('heroicon-o-document-check')
                ->color('success'),
            Stat::make('دپارتمان با بیشترین گزارش', Report::select('department_id', DB::raw('count(*) as total'))
                                     ->groupBy('department_id')
                                     ->orderByDesc('total')
                                     ->first()?->department?->name ?? 'نامشخص')
                ->icon('heroicon-o-chart-bar')
                ->color('warning'),
        ];
    }
}
