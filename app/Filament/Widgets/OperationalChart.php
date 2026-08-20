<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\UserResource\Enums\UserType;
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


class OperationalChart extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = true;
    protected static ?int $sort = 7;
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
                    'module_a' => __('resources/dashboard/strings.operational_chart.modules.module_a'),
                    'module_b' => __('resources/dashboard/strings.operational_chart.modules.module_b'),
                    'module_c' => __('resources/dashboard/strings.operational_chart.modules.module_c'),
                    'module_d' => __('resources/dashboard/strings.operational_chart.modules.module_d'),
                ]),
        ]);
    }

    public function getDescription(): ?string
    {
        return match ($this->activeModule()) {
            'module_a' => 'داده از پرسشنامه انرژی («امتیاز کلی» هر پرسنل در ۳۰ روز اخیر) و تسک بورد (وظایف در «وضعیت» «انجام نشده» یا «در حال انجام») به تفکیک «واحد» سازمانی کنار هم قرار می‌گیرد. '
                . 'اگر واحدی هم‌زمان انرژی پایین و وظایف انباشته داشت، احتمال فرسودگی شغلی در آن واحد بالاست. '
                . '(منابع: پرسشنامه انرژی ← «امتیاز کلی»، «تکمیل شده در» | تسک بورد ← «وضعیت»، «مسئول انجام» | پروفایل پرسنلی ← «واحد»)',

            'module_b' => 'از سیستم تیکت، «تاریخ ثبت» و «تاریخ بسته‌شدن» هر تیکت کنار هم قرار می‌گیرند و اختلاف روزهای آن‌ها به تفکیک «واحد سازمانی» درخواست‌دهنده میانگین‌گیری می‌شود. '
                . 'هر واحدی که عدد بالاتری دارد مدت بیشتری برای دریافت پشتیبانی منتظر مانده است. '
                . '(منابع: سیستم تیکت ← «تاریخ ثبت»، «تاریخ بسته‌شدن»، «درخواست‌دهنده» | پروفایل پرسنلی ← «واحد»)',

            'module_c' => 'از سیستم پیشنهادات، «مرحله» جاری هر پیشنهاد گرفته و تعداد پیشنهادات در هر مرحله شمارش می‌شود. '
                . 'اگر بیشتر پیشنهادات در «منتظر تصمیم» گیر کرده باشند، گلوگاه تصمیم‌گیری در سطح مدیریت است. '
                . '(منابع: پیشنهادها ← «مرحله»)',

            'module_d' => 'از سیستم رزرو، ساعت «زمان شروع» هر رزرو (جای پارک، صندلی، خودرو یا جلسه) استخراج و تعداد رزروها در هر ساعت از شبانه‌روز جمع‌بندی می‌شود. '
                . 'ساعات شلوغ نشان می‌دهد کجا باید منابع بیشتری اضافه یا زمان‌بندی‌ها پراکنده‌تر شوند. '
                . '(منابع: رزروها ← «زمان شروع»)',

            default => __('resources/dashboard/strings.chart_widgets.default_description'),
        };
    }

    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(Blade::render(
            '<span class="relative -top-5 w-full !mb-0 !pb-0"><x-ui.title icon="analytics" :title="$title" count="4" countLabel="آیتم آماری" /></span>',
            ['title' => __('resources/dashboard/strings.operational_chart.heading')]
        ));
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleAData(string $departmentCode): array
    {
        $energyAgg = DB::table('energy_tests')
            ->join('users', 'users.id', '=', 'energy_tests.user_id')
            ->join('profiles', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->select('profiles.department_id as code', DB::raw('AVG(energy_tests.overall_score) as avg_energy'))
            ->where('energy_tests.completed_at', '>=', now()->subDays(30))
            ->groupBy('profiles.department_id');

        $tasksAgg = DB::table('tasks')
            ->join('users', 'users.id', '=', 'tasks.assigned_to')
            ->join('profiles', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->select('profiles.department_id as code', DB::raw('COUNT(tasks.id) as pending_tasks'))
            ->whereIn('tasks.status', ['todo', 'in-progress'])
            ->groupBy('profiles.department_id');

        $query = DB::table('departments')
            ->select(
                DB::raw('COALESCE(NULLIF(departments.description, ""), departments.name, departments.code) as department_name'),
                DB::raw('COALESCE(e.avg_energy, 0) as avg_energy'),
                DB::raw('COALESCE(t.pending_tasks, 0) as pending_tasks')
            )
            ->leftJoinSub($energyAgg, 'e', 'e.code', '=', 'departments.code')
            ->leftJoinSub($tasksAgg, 't', 't.code', '=', 'departments.code')
            ->orderBy('departments.code');

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
                DB::raw('COALESCE(NULLIF(creator_dept.description, ""), creator_dept.name, creator_dept.code) as origin'),
                DB::raw('ROUND(AVG(DATEDIFF(tickets.completion_date, DATE(tickets.created_at))), 1) as avg_resolution_days')
            )
            ->join('users as requester', 'tickets.requester_id', '=', 'requester.id')
            ->join('profiles as requester_profile', 'requester.id', '=', 'requester_profile.user_id')
            ->join('departments as creator_dept', 'requester_profile.department_id', '=', 'creator_dept.code')
            ->where('requester.type', '!=', UserType::Guest->value)
            ->whereNotNull('tickets.completion_date')
            ->groupBy('creator_dept.code', 'creator_dept.name', 'creator_dept.description');

        if ($departmentCode) {
            $query->where('creator_dept.code', $departmentCode);
        }

        $results = $query->get();

        return [
            'datasets' => [
                ['label' => 'میانگین روزهای رفع تیکت', 'data' => $results->pluck('avg_resolution_days')->toArray(), 'backgroundColor' => '#ef4444'],
            ],
            'labels' => $results->pluck('origin')->toArray(),
        ];
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
            'module_b' => $this->getModuleBData($dept),
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

        if ($module === 'module_b') {
            $baseOptions['indexAxis'] = 'y';
            $baseOptions['scales']['x']['beginAtZero'] = true;
            return $baseOptions;
        }

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
