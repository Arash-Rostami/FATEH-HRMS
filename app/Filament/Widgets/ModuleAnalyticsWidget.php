<?php

namespace App\Filament\Widgets;

use App\Models\Ad;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

class ModuleAnalyticsWidget extends Widget
{
    protected string $view = 'filament.widgets.module-analytics-widget';

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

    #[Computed]
    public function usersData(): array
    {
        if ($this->activeTab !== 'users') return [];

        return [
            'total' => User::count(),
            'active' => User::active()->count(),
            'admins' => User::whereIn('role', ['admin', 'developer'])->count(),
            'online' => User::online()->count(),
        ];
    }

    #[Computed]
    public function departmentsData(): array
    {
        if ($this->activeTab !== 'departments') return [];

        return [
            'total' => Department::count(),
            'with_users' => Department::has('users')->count(),
            'most_populated' => Department::withCount('users')->orderByDesc('users_count')->first()?->name ?? 'نامشخص',
        ];
    }

    #[Computed]
    public function adsData(): array
    {
        if ($this->activeTab !== 'ads') return [];

        return [
            'total' => Ad::count(),
            'active' => Ad::countActiveJobs(),
            'males' => Ad::countMales(),
            'females' => Ad::countFemales(),
            'any' => Ad::countBothGender(),
        ];
    }

    #[Computed]
    public function reportsData(): array
    {
        if ($this->activeTab !== 'reports') return [];

        return [
            'total' => Report::count(),
            'active' => Report::active()->count(),
            'by_department' => Report::select('department_id', DB::raw('count(*) as total'))
                                     ->groupBy('department_id')
                                     ->orderByDesc('total')
                                     ->first()?->department?->name ?? 'نامشخص',
        ];
    }
}
