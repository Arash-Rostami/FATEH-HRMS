<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;


class ModuleAnalyticsChartsLeft extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = true;
    protected static ?int $sort = 4;
    protected bool $hasDeferredFilters = true;
    protected int|string|array $columnSpan = ['sm' => 'full', 'md' => 1];

    public function filtersApplyAction(Action $action): Action
    {
        return $action->label('اعمال')->color('success');
    }

    public function filtersResetAction(Action $action): Action
    {
        return $action->label('بازنشانی');
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Radio::make('module')
                ->label('ماژول تحلیلی')
                ->default(null)
                ->options([
                    'module_a' => 'شاخص پیش‌بینی فرسودگی سرمایه انسانی',
                    'module_b' => 'شاخص اصطکاک و تاخیر بین واحدها',
                    'module_c' => 'قیف پیشرفت نوآوری و پیشنهادات',
                    'module_d' => 'پراکندگی و تراکم استفاده از منابع',
                ]),
        ]);
    }

    public function getDescription(): ?string
    {
        return match ($this->activeModule()) {
            'module_a' => 'این نمودار ارتباط بین میزان انرژی ثبت شده پرسنل و حجم وظایف باز در هر واحد را نشان می‌دهد. افت انرژی همزمان با افزایش وظایف، زنگ خطر فرسودگی است.',
            'module_b' => 'نمایشگر زمان صرف شده برای حل تیکت‌ها بین واحدهای مختلف. رنگ‌های متمایل به قرمز نشان‌دهنده گلوگاه‌های ارتباطی و تاخیر در پاسخگویی است.',
            'module_c' => 'نمایش وضعیت فعلی ایده‌ها و پیشنهادات ثبت شده در مراحل مختلف تایید تا اجرا، جهت بررسی سرعت چرخه نوآوری سازمان.',
            'module_d' => 'نمایش ساعات اوج استفاده از منابع و اتاق‌های جلسات در طول شبانه‌روز، جهت بهینه‌سازی مصرف انرژی و زمان‌بندی بهتر.',
            default => 'لطفاً برای مشاهده آمار دقیق، یکی از ماژول‌های تحلیلی را انتخاب کنید.',
        };
    }

    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(Blade::render('<span class="relative -top-5 w-full !mb-0 !pb-0"><x-ui.title icon="analytics" title="تحلیل‌های منابع و عملکرد" count="4" countLabel="آیتم آماری" /></span>'));
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
                ['label' => 'میانگین انرژی (۳۰ روز اخیر)', 'data' => $results->pluck('avg_energy')->toArray(), 'type' => 'line', 'borderColor' => '#10b981'],
                ['label' => 'وظایف باز یا در حال انجام', 'data' => $results->pluck('pending_tasks')->toArray(), 'type' => 'bar', 'backgroundColor' => '#f59e0b'],
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
            $query->where(function ($q) use ($departmentCode) {
                $q->where('creator_dept.code', $departmentCode)
                    ->orWhere('assignee_dept.code', $departmentCode);
            });
        }

        return $query->get()->toArray();
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleCData(string $departmentCode): array
    {
        $results = DB::table('suggestions')
            ->select('stage', DB::raw('COUNT(id) as count'))
            ->groupBy('stage')
            ->get()
            ->pluck('count', 'stage')
            ->toArray();

        $stageTranslations = [
            'pending' => 'در انتظار بررسی',
            'team_remarks' => 'نظرات تیم',
            'dept_remarks' => 'نظرات واحد',
            'awaiting_decision' => 'در انتظار تصمیم',
            'accepted' => 'تایید شده',
            'rejected' => 'رد شده',
            'under_review' => 'در حال بررسی',
            'closed' => 'بسته شده',
        ];

        $data = [];
        $labels = [];

        foreach ($stageTranslations as $stage => $label) {
            $data[] = $results[$stage] ?? 0;
            $labels[] = $label;
        }

        return [
            'datasets' => [
                ['label' => 'تعداد پیشنهادات', 'data' => $data, 'backgroundColor' => '#3b82f6'],
            ],
            'labels' => $labels,
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
                ['label' => 'تراکم رزرو منابع (ساعت)', 'data' => $data, 'backgroundColor' => 'rgba(139, 92, 246, 0.4)', 'borderColor' => '#8b5cf6', 'fill' => true],
            ],
            'labels' => $labels,
        ];
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
            'module_a' => $this->getModuleAData($dept),
            'module_c' => $this->getModuleCData($dept),
            'module_d' => $this->getModuleDData($dept),
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

        if ($module === 'module_c') {
            $baseOptions['indexAxis'] = 'y';
            return $baseOptions;
        }

        if ($module === 'module_d') {
            $baseOptions['scales']['y']['beginAtZero'] = true;
            $baseOptions['plugins']['filler'] = ['propagate' => false];
            return $baseOptions;
        }

        return $baseOptions;
    }

    protected function getType(): string
    {
        return match ($this->activeModule()) {
            'module_a', 'module_d' => 'line',
            default => 'bar',
        };
    }

    private function getScopeCondition(): string
    {
        return auth()->user()->profile?->department_id ?? '';
    }
}
