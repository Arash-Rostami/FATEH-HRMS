<?php

namespace App\Filament\Widgets;

use App\Enums\UserType;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Morilog\Jalali\Jalalian;

class StructuralChart extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = true;
    protected static ?int $sort = 8;
    protected bool $hasDeferredFilters = true;
    protected int|string|array $columnSpan = ['sm' => 'full', 'md' => 1];

    public function filtersApplyAction(Action $action): Action
    {
        return $action->label(__('resources/dashboard/strings.chart_widgets.filters.apply'))->color('success');
    }

    public function filtersResetAction(Action $action): Action
    {
        return $action->label(__('resources/dashboard/strings.chart_widgets.filters.reset'));
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Radio::make('module')
                ->label(__('resources/dashboard/strings.chart_widgets.filters.module_field'))
                ->default(null)
                ->options([
                    'module_e' => __('resources/dashboard/strings.structural_chart.modules.module_e'),
                    'module_f' => __('resources/dashboard/strings.structural_chart.modules.module_f'),
                    'module_g' => __('resources/dashboard/strings.structural_chart.modules.module_g'),
                    'module_h' => __('resources/dashboard/strings.structural_chart.modules.module_h'),
                    'module_i' => __('resources/dashboard/strings.structural_chart.modules.module_i'),
                ]),
        ]);
    }

    public function getDescription(): ?string
    {
        return match ($this->activeModule()) {
            'module_e' => 'تعداد وظایف انجام‌نشده یا در حال انجام و تیکت‌های باز یا در حال بررسی را نشان می‌دهد. هر مورد در واحد مسئول رسیدگی یا انجام کار شمرده می‌شود.',

            'module_f' => 'تعداد کارکنان را بر اساس جنسیت و وضعیت اشتغال ثبت‌شده در پروفایل نشان می‌دهد.',

            'module_g' => 'تعداد اطلاعیه‌های ثبت‌شده در هر روز از ۳۰ روز اخیر را نشان می‌دهد. روزهای بدون اطلاعیه با صفر نمایش داده می‌شوند.',

            'module_h' => 'تعداد کارکنان و تعداد گزارش‌های ثبت‌شده توسط کاربران هر واحد را کنار هم نشان می‌دهد.',

            'module_i' => 'سابقهٔ خدمت از تاریخ شروع کار محاسبه می‌شود. نمودار تعداد کارکنان را در هر بازهٔ سابقه نشان می‌دهد.',

            default => __('resources/dashboard/strings.chart_widgets.default_description'),
        };
    }


    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(Blade::render(
            '<span class="relative -top-5 w-full !mb-0 !pb-0"><x-ui.title icon="analytics" :title="$title" count="5" countLabel="آیتم آماری" /></span>',
            ['title' => __('resources/dashboard/strings.structural_chart.heading')]
        ));
    }

    public function getModuleEData(string $departmentCode): array
    {
        return ModelCacheVersion::rememberGlobal("struct:module_e:{$departmentCode}", function () use ($departmentCode) {
            $query = DB::table('departments')
                ->leftJoinSub(
                    DB::table('tasks')
                        ->join('users', 'tasks.assigned_to', '=', 'users.id')
                        ->join('profiles', 'users.id', '=', 'profiles.user_id')
                        ->where('users.type', '!=', UserType::Guest->value)
                        ->whereIn('tasks.status', ['todo', 'in-progress'])
                        ->select('profiles.department_id as code', DB::raw('COUNT(*) as task_count'))
                        ->groupBy('profiles.department_id'),
                    'task_agg',
                    'task_agg.code',
                    '=',
                    'departments.code'
                )
                ->leftJoinSub(
                    DB::table('tickets')
                        ->join('users', 'tickets.assigned_to', '=', 'users.id')
                        ->join('profiles', 'users.id', '=', 'profiles.user_id')
                        ->where('users.type', '!=', UserType::Guest->value)
                        ->whereIn('tickets.status', ['open', 'in-progress'])
                        ->select('profiles.department_id as code', DB::raw('COUNT(*) as ticket_count'))
                        ->groupBy('profiles.department_id'),
                    'ticket_agg',
                    'ticket_agg.code',
                    '=',
                    'departments.code'
                )
                ->select(
                    DB::raw('COALESCE(NULLIF(departments.description, ""), departments.name, departments.code) as department_name'),
                    DB::raw('COALESCE(task_agg.task_count, 0) as task_count'),
                    DB::raw('COALESCE(ticket_agg.ticket_count, 0) as ticket_count')
                );

            if ($departmentCode) {
                $query->where('departments.code', $departmentCode);
            }

            $results = $query->get();

            return [
                'datasets' => [
                    ['label' => 'وظایف انجام‌نشده و در حال انجام', 'data' => $results->pluck('task_count')->toArray(), 'backgroundColor' => '#3b82f6'],
                    ['label' => 'تیکت‌های باز و در حال بررسی', 'data' => $results->pluck('ticket_count')->toArray(), 'backgroundColor' => '#ef4444'],
                ],
                'labels' => $results->pluck('department_name')->toArray(),
            ];
        });
    }

    public function getModuleFData(string $departmentCode): array
    {
        return ModelCacheVersion::rememberGlobal("struct:module_f:{$departmentCode}", function () use ($departmentCode) {
            $query = DB::table('profiles')
                ->join('users', 'profiles.user_id', '=', 'users.id')
                ->where('users.type', '!=', UserType::Guest->value)
                ->select('gender', 'employment_status', DB::raw('COUNT(profiles.id) as count'))
                ->groupBy('gender', 'employment_status');

            if ($departmentCode) {
                $query->where('department_id', $departmentCode);
            }

            $results = $query->get();

            $genderMap = ['male' => 'آقا', 'female' => 'خانم'];
            $statusMap = ['probational' => 'آزمایشی', 'working' => 'فعال', 'terminated' => 'خاتمه‌یافته'];

            $labels = [];
            $data = [];
            foreach ($results as $row) {
                $labels[] = ($genderMap[$row->gender] ?? 'نامشخص') . ' - ' . ($statusMap[$row->employment_status] ?? 'نامشخص');
                $data[] = $row->count;
            }

            return [
                'datasets' => [
                    ['label' => 'تعداد کارکنان', 'data' => $data, 'backgroundColor' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b']],
                ],
                'labels' => $labels,
            ];
        });
    }

    public function getModuleGData(string $departmentCode): array
    {
        return ModelCacheVersion::rememberGlobal("struct:module_g", function () {
            $startDate = Carbon::now()->subDays(29)->startOfDay();
            $results = DB::table('posts')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(id) as count'))
                ->where('created_at', '>=', $startDate)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get()
                ->keyBy('date');

            $labels = [];
            $data = [];
            for ($i = 0; $i <= 29; $i++) {
                $currentDate = (clone $startDate)->addDays($i);
                $dateStr = $currentDate->format('Y-m-d');
                try {
                    $labels[] = Jalalian::fromCarbon($currentDate)->format('%m/%d');
                } catch (\Exception $e) {
                    $labels[] = $dateStr;
                }
                $data[] = $results->get($dateStr)?->count ?? 0;
            }

            return [
                'datasets' => [
                    ['label' => 'اطلاعیه‌های ثبت‌شده', 'data' => $data, 'borderColor' => '#0ea5e9', 'backgroundColor' => 'rgba(14, 165, 233, 0.2)', 'fill' => true],
                ],
                'labels' => $labels,
            ];
        });
    }

    public function getModuleHData(string $departmentCode): array
    {
        return ModelCacheVersion::rememberGlobal("struct:module_h:{$departmentCode}", function () use ($departmentCode) {
            $query = DB::table('departments')
                ->leftJoinSub(
                    DB::table('profiles')
                        ->join('users', 'profiles.user_id', '=', 'users.id')
                        ->tap(User::currentStaff(...))
                        ->select('profiles.department_id as code', DB::raw('COUNT(*) as users_count'))
                        ->groupBy('profiles.department_id'),
                    'prof_agg',
                    'prof_agg.code',
                    '=',
                    'departments.code'
                )
                ->leftJoinSub(
                    DB::table('reports')
                        ->join('users', 'reports.user_id', '=', 'users.id')
                        ->join('profiles', 'users.id', '=', 'profiles.user_id')
                        ->where('users.type', '!=', UserType::Guest->value)
                        ->select('profiles.department_id as code', DB::raw('COUNT(*) as reports_count'))
                        ->groupBy('profiles.department_id'),
                    'rep_agg',
                    'rep_agg.code',
                    '=',
                    'departments.code'
                )
                ->select(
                    DB::raw('COALESCE(NULLIF(departments.description, ""), departments.name, departments.code) as department_name'),
                    DB::raw('COALESCE(prof_agg.users_count, 0) as users_count'),
                    DB::raw('COALESCE(rep_agg.reports_count, 0) as reports_count')
                )
                ->orderBy('departments.id')
                ->limit(10);

            if ($departmentCode) {
                $query->where('departments.code', $departmentCode);
            }

            $results = $query->get();

            return [
                'datasets' => [
                    ['label' => 'کارکنان', 'data' => $results->pluck('users_count')->toArray(), 'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16, 185, 129, 0.2)'],
                    ['label' => 'گزارش‌ها', 'data' => $results->pluck('reports_count')->toArray(), 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239, 68, 68, 0.2)'],
                ],
                'labels' => $results->pluck('department_name')->toArray(),
            ];
        });
    }

    private function getModuleIData(string $departmentCode): array
    {
        return ModelCacheVersion::rememberGlobal("struct:module_i:{$departmentCode}", function () use ($departmentCode) {
            $query = DB::table('profiles')
                ->join('users', 'profiles.user_id', '=', 'users.id')
                ->tap(User::currentStaff(...))
                ->whereNotNull('start_date');

            if ($departmentCode) {
                $query->where('department_id', $departmentCode);
            }

            $row = $query->selectRaw("
                COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(YEAR, start_date, NOW()) < 1 THEN 1 ELSE 0 END), 0) as b1,
                COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(YEAR, start_date, NOW()) BETWEEN 1 AND 2 THEN 1 ELSE 0 END), 0) as b2,
                COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(YEAR, start_date, NOW()) BETWEEN 3 AND 4 THEN 1 ELSE 0 END), 0) as b3,
                COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(YEAR, start_date, NOW()) BETWEEN 5 AND 9 THEN 1 ELSE 0 END), 0) as b4,
                COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(YEAR, start_date, NOW()) >= 10 THEN 1 ELSE 0 END), 0) as b5
            ")->first();

            return [
                'datasets' => [[
                    'label' => 'تعداد کارکنان',
                    'data' => [$row->b1, $row->b2, $row->b3, $row->b4, $row->b5],
                    'backgroundColor' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
                ]],
                'labels' => ['کمتر از ۱ سال', '۱ تا ۲ سال', '۳ تا ۴ سال', '۵ تا ۹ سال', '۱۰ سال و بیشتر'],
            ];
        });
    }

    protected function activeModule(): ?string
    {
        return $this->filters['module'] ?? null;
    }

    protected function getData(): array
    {
        $module = $this->activeModule();

        if (!$module) {
            return ['datasets' => [], 'labels' => []];
        }

        $dept = $this->getScopeCondition();

        return match ($module) {
            'module_e' => $this->getModuleEData($dept),
            'module_f' => $this->getModuleFData($dept),
            'module_g' => $this->getModuleGData($dept),
            'module_h' => $this->getModuleHData($dept),
            'module_i' => $this->getModuleIData($dept),
            default => ['datasets' => [], 'labels' => []],
        };
    }

    protected function getOptions(): array
    {
        $fontFamily = 'Yekan Bakh, Yekan, Tahoma, sans-serif';

        $baseOptions = [
            'plugins' => [
                'legend' => [
                    'labels' => ['font' => ['family' => $fontFamily]],
                ],
            ],
            'scales' => [
                'x' => ['ticks' => ['font' => ['family' => $fontFamily]]],
                'y' => ['ticks' => ['font' => ['family' => $fontFamily]]],
            ],
        ];

        $module = $this->activeModule();

        if ($module === 'module_e') {
            $baseOptions['scales']['x']['stacked'] = true;
            $baseOptions['scales']['y']['stacked'] = true;
            return $baseOptions;
        }

        if ($module === 'module_f' || $module === 'module_h') {
            unset($baseOptions['scales']);
            return $baseOptions;
        }

        return $baseOptions;
    }

    protected function getType(): string
    {
        return match ($this->activeModule()) {
            'module_e' => 'bar',
            'module_f' => 'doughnut',
            'module_g' => 'line',
            'module_h' => 'radar',
            'module_i' => 'bar',
            default => 'bar',
        };
    }

    private function getScopeCondition(): string
    {
        return auth()->user()->profile?->department_id ?? '';
    }
}
