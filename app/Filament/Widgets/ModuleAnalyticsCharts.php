<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Computed;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class ModuleAnalyticsCharts extends ChartWidget
{
    protected static ?string $heading = 'Analytics Diagrams';
    protected static bool $isLazy = true;
    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        return [
            'module_a' => 'Human Capital Burnout Predictor',
            'module_b' => 'Inter-Departmental Friction Index',
            'module_c' => 'Innovation Funnel',
            'module_d' => 'Asset Saturation',
            'module_e' => 'Workload Distribution',
            'module_f' => 'Demographic Activity',
            'module_g' => 'Engagement Timeline',
            'module_h' => 'Compliance Density',
            'module_i' => 'Recruitment Funnel',
        ];
    }

    protected function getData(): array
    {
        if (!$this->filter) {
            return ['datasets' => [], 'labels' => []];
        }

        $dept = $this->getScopeCondition();

        return match ($this->filter) {
            'module_a' => $this->getModuleAData($dept),
            'module_b' => [],
            'module_c' => $this->getModuleCData($dept),
            'module_d' => $this->getModuleDData($dept),
            'module_e' => $this->getModuleEData($dept),
            'module_f' => $this->getModuleFData($dept),
            'module_g' => $this->getModuleGData($dept),
            'module_h' => $this->getModuleHData($dept),
            'module_i' => $this->getModuleIData($dept),
            default => ['datasets' => [], 'labels' => []],
        };
    }

    protected function getType(): string
    {
        return match ($this->filter) {
            'module_a' => 'line',
            'module_c' => 'bar',
            'module_d' => 'line',
            'module_e' => 'bar',
            'module_f' => 'doughnut',
            'module_g' => 'line',
            'module_h' => 'radar',
            'module_i' => 'bar',
            default => 'bar',
        };
    }

    protected function getOptions(): array
    {
        if (in_array($this->filter, ['module_c', 'module_i'])) {
            return ['indexAxis' => 'y'];
        }

        if ($this->filter === 'module_d') {
             return [
                'scales' => ['y' => ['beginAtZero' => true]],
                'plugins' => ['filler' => ['propagate' => false]],
             ];
        }

        if ($this->filter === 'module_e') {
            return [
                'scales' => [
                    'x' => ['stacked' => true],
                    'y' => ['stacked' => true],
                ],
            ];
        }

        return [];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        if ($this->filter === 'module_b') {
            return view('livewire.filament.widgets.module-analytics-charts-friction', [
                'frictionData' => $this->getModuleBData($this->getScopeCondition()),
                'filter' => $this->filter,
            ]);
        }

        return parent::render();
    }

    private function getScopeCondition(): string
    {
        return auth()->user()->profile?->department_id ?? '';
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleAData(string $departmentCode): array
    {
        $query = DB::table('departments')
            ->select(
                'departments.name as department_name',
                DB::raw('COALESCE(AVG(energy_tests.overall_score), 0) as avg_energy'),
                DB::raw('COUNT(tasks.id) as pending_tasks')
            )
            ->leftJoin('profiles', 'departments.code', '=', 'profiles.department_id')
            ->leftJoin('users', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('energy_tests', function ($join) {
                $join->on('users.id', '=', 'energy_tests.user_id')
                    ->where('energy_tests.completed_at', '>=', now()->subDays(30));
            })
            ->leftJoin('tasks', function ($join) {
                $join->on('users.id', '=', 'tasks.assigned_to')
                    ->whereIn('tasks.status', ['todo', 'in-progress']);
            })
            ->groupBy('departments.code', 'departments.name');

        if ($departmentCode) {
            $query->where('departments.code', $departmentCode);
        }

        $results = $query->get();

        return [
            'datasets' => [
                ['label' => 'Average Energy (30 Days)', 'data' => $results->pluck('avg_energy')->toArray(), 'type' => 'line', 'borderColor' => '#10b981'],
                ['label' => 'Pending/In-Progress Tasks', 'data' => $results->pluck('pending_tasks')->toArray(), 'type' => 'bar', 'backgroundColor' => '#f59e0b'],
            ],
            'labels' => $results->pluck('department_name')->toArray(),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleBData(string $departmentCode): array
    {
        $query = DB::table('tickets')
            ->select(
                'creator_dept.name as origin',
                'assignee_dept.name as destination',
                DB::raw('COALESCE(AVG(TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.resolved_at)), 0) as avg_resolution_time')
            )
            ->join('users as requester', 'tickets.requester_id', '=', 'requester.id')
            ->join('profiles as requester_profile', 'requester.id', '=', 'requester_profile.user_id')
            ->join('departments as creator_dept', 'requester_profile.department_id', '=', 'creator_dept.code')
            ->join('users as assignee', 'tickets.assigned_to', '=', 'assignee.id')
            ->join('profiles as assignee_profile', 'assignee.id', '=', 'assignee_profile.user_id')
            ->join('departments as assignee_dept', 'assignee_profile.department_id', '=', 'assignee_dept.code')
            ->whereNotNull('tickets.resolved_at')
            ->groupBy('creator_dept.code', 'creator_dept.name', 'assignee_dept.code', 'assignee_dept.name');

        if ($departmentCode) {
             $query->where(function($q) use ($departmentCode) {
                 $q->where('creator_dept.code', $departmentCode)
                   ->orWhere('assignee_dept.code', $departmentCode);
             });
        }

        return $query->get()->toArray();
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleCData(string $departmentCode): array
    {
        $query = DB::table('suggestions')
            ->select('stage', DB::raw('COUNT(id) as count'))
            ->groupBy('stage');

        $results = $query->get()->pluck('count', 'stage')->toArray();

        $stages = ['pending', 'team_remarks', 'dept_remarks', 'awaiting_decision', 'accepted', 'rejected', 'under_review', 'closed'];
        $data = [];
        foreach ($stages as $stage) {
            $data[] = $results[$stage] ?? 0;
        }

        return [
            'datasets' => [
                ['label' => 'Suggestions Count', 'data' => $data, 'backgroundColor' => '#3b82f6'],
            ],
            'labels' => array_map('ucfirst', str_replace('_', ' ', $stages)),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleDData(string $departmentCode): array
    {
        $results = DB::table('reservations')
            ->select(DB::raw('HOUR(start_time) as hour_of_day'), DB::raw('COUNT(id) as reservation_count'))
            ->groupBy(DB::raw('HOUR(start_time)'))
            ->orderBy(DB::raw('HOUR(start_time)'))
            ->get();

        $data = array_fill(0, 24, 0);
        foreach ($results as $row) {
            $data[$row->hour_of_day] = $row->reservation_count;
        }

        $labels = array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23));

        return [
            'datasets' => [
                ['label' => 'Reservations Heatmap', 'data' => $data, 'backgroundColor' => '#8b5cf6', 'fill' => true],
            ],
            'labels' => $labels,
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleEData(string $departmentCode): array
    {
        $query = DB::table('departments')
            ->select(
                'departments.name as department_name',
                DB::raw('(SELECT COUNT(*) FROM tasks INNER JOIN users ON tasks.assigned_to = users.id INNER JOIN profiles ON users.id = profiles.user_id WHERE profiles.department_id = departments.code AND tasks.status IN ("todo", "in-progress")) as task_count'),
                DB::raw('(SELECT COUNT(*) FROM tickets INNER JOIN users ON tickets.assigned_to = users.id INNER JOIN profiles ON users.id = profiles.user_id WHERE profiles.department_id = departments.code AND tickets.status IN ("open", "in-progress")) as ticket_count')
            );

        if ($departmentCode) {
            $query->where('departments.code', $departmentCode);
        }

        $results = $query->get();

        return [
            'datasets' => [
                ['label' => 'Open Tasks', 'data' => $results->pluck('task_count')->toArray(), 'backgroundColor' => '#3b82f6'],
                ['label' => 'Open Tickets', 'data' => $results->pluck('ticket_count')->toArray(), 'backgroundColor' => '#ef4444'],
            ],
            'labels' => $results->pluck('department_name')->toArray(),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleFData(string $departmentCode): array
    {
        $query = DB::table('profiles')
            ->select('gender', 'employment_status', DB::raw('COUNT(id) as count'))
            ->groupBy('gender', 'employment_status');

        if ($departmentCode) {
            $query->where('department_id', $departmentCode);
        }

        $results = $query->get();

        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $labels[] = ($row->gender ?? 'Unknown') . ' - ' . ($row->employment_status ?? 'Unknown');
            $data[] = $row->count;
        }

        return [
            'datasets' => [
                ['label' => 'Demographics', 'data' => $data, 'backgroundColor' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b']],
            ],
            'labels' => $labels,
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleGData(string $departmentCode): array
    {
        $query = DB::table('posts')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(id) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->limit(30);

        $results = $query->get();

        $labels = [];
        $data = [];
        foreach ($results as $row) {
            try {
                $labels[] = Jalalian::fromCarbon(Carbon::parse($row->date))->format('%m/%d');
            } catch (\Exception $e) {
                $labels[] = $row->date;
            }
            $data[] = $row->count;
        }

        return [
            'datasets' => [
                ['label' => 'Posts Created', 'data' => $data, 'borderColor' => '#0ea5e9', 'fill' => true],
            ],
            'labels' => $labels,
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleHData(string $departmentCode): array
    {
        $query = DB::table('departments')
            ->select(
                'departments.name as department_name',
                DB::raw('(SELECT COUNT(*) FROM profiles WHERE profiles.department_id = departments.code) as users_count'),
                DB::raw('(SELECT COUNT(*) FROM reports INNER JOIN users ON reports.user_id = users.id INNER JOIN profiles ON users.id = profiles.user_id WHERE profiles.department_id = departments.code) as reports_count')
            )
            ->limit(10);

        if ($departmentCode) {
            $query->where('departments.code', $departmentCode);
        }

        $results = $query->get();

        return [
            'datasets' => [
                ['label' => 'Users', 'data' => $results->pluck('users_count')->toArray(), 'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16, 185, 129, 0.2)'],
                ['label' => 'Reports', 'data' => $results->pluck('reports_count')->toArray(), 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239, 68, 68, 0.2)'],
            ],
            'labels' => $results->pluck('department_name')->toArray(),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleIData(string $departmentCode): array
    {
        $adsCount = DB::table('ads')->where('active', 1)->count();
        $onboardingCount = DB::table('onboardings')->where('is_active', 1)->count();

        return [
            'datasets' => [
                ['label' => 'Counts', 'data' => [$adsCount, $onboardingCount], 'backgroundColor' => ['#3b82f6', '#f59e0b']],
            ],
            'labels' => ['Active Job Ads', 'Active Onboardings'],
        ];
    }
}
