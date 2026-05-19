<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

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
        ];
    }

    protected function getData(): array
    {
        if (!$this->filter) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        return match ($this->filter) {
            'module_a' => $this->getModuleAData(),
            'module_b' => [],
            'module_c' => $this->getModuleCData(),
            'module_d' => $this->getModuleDData(),
            default => ['datasets' => [], 'labels' => []],
        };
    }

    protected function getType(): string
    {
        return match ($this->filter) {
            'module_a' => 'line',
            'module_c' => 'bar',
            'module_d' => 'line',
            default => 'bar',
        };
    }

    protected function getOptions(): array
    {
        if ($this->filter === 'module_c') {
            return [
                'indexAxis' => 'y',
            ];
        }

        if ($this->filter === 'module_d') {
             return [
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
                'plugins' => [
                    'filler' => [
                        'propagate' => false,
                    ],
                ],
             ];
        }

        return [];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        if ($this->filter === 'module_b') {
            return view('livewire.filament.widgets.module-analytics-charts-friction', [
                'frictionData' => $this->getModuleBData(),
                'filter' => $this->filter,
            ]);
        }

        return parent::render();
    }

    private function getScopeCondition(): string
    {
        return auth()->user()->profile?->department_id ?? '';
    }

    private function getModuleAData(): array
    {
        $departmentCode = $this->getScopeCondition();

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
                [
                    'label' => 'Average Energy (30 Days)',
                    'data' => $results->pluck('avg_energy')->toArray(),
                    'type' => 'line',
                    'borderColor' => '#10b981',
                ],
                [
                    'label' => 'Pending/In-Progress Tasks',
                    'data' => $results->pluck('pending_tasks')->toArray(),
                    'type' => 'bar',
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $results->pluck('department_name')->toArray(),
        ];
    }

    private function getModuleBData(): array
    {
        $departmentCode = $this->getScopeCondition();

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

    private function getModuleCData(): array
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
                [
                    'label' => 'Suggestions Count',
                    'data' => $data,
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => array_map('ucfirst', str_replace('_', ' ', $stages)),
        ];
    }

    private function getModuleDData(): array
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

        $labels = array_map(function($hour) {
            return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
        }, range(0, 23));

        return [
            'datasets' => [
                [
                    'label' => 'Reservations Heatmap',
                    'data' => $data,
                    'backgroundColor' => '#8b5cf6',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
