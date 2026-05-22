<?php

namespace App\Filament\Widgets;

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
use Livewire\Attributes\Computed;
use Morilog\Jalali\Jalalian;

class ModuleAnalyticsChartsRight extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = true;
    protected static ?int $sort = 5;
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
                    'module_e' => 'توزیع بار کاری (وظایف و تیکت‌ها)',
                    'module_f' => 'ترکیب جمعیتی و وضعیت اشتغال',
                    'module_g' => 'روند تعاملات و تولید محتوا',
                    'module_h' => 'تراکم گزارشات و نظارت پذیری',
                    'module_i' => 'قیف جذب و آنبوردینگ نیروها',
                ]),
        ]);
    }

    public function getDescription(): ?string
    {
        return match ($this->activeModule()) {
            'module_e' => 'مقایسه حجم تیکت‌های باز (درخواست‌ها) و وظایف در حال انجام به تفکیک هر واحد، جهت شناسایی واحدهای دارای بار کاری نامتوازن.',
            'module_f' => 'بررسی ساختار جمعیتی پرسنل شامل جنسیت و وضعیت قراردادها جهت تصمیم‌گیری بهتر در سیاست‌های منابع انسانی.',
            'module_g' => 'روند انتشار پست‌ها و اخبار در ۳۰ روز گذشته که نشان‌دهنده میزان پویایی و ارتباطات داخلی سازمان است.',
            'module_h' => 'نسبت کاربران فعال به تعداد گزارشات ثبت شده در هر واحد، که نشانگر سطح قانون‌مداری و شفافیت عملکردی است.',
            'module_i' => 'مقایسه تعداد آگهی‌های استخدامی فعال با تعداد پرونده‌های در جریان آنبوردینگ، جهت سنجش سرعت فرآیند جذب استعدادها.',
            default => 'لطفاً برای مشاهده آمار دقیق، یکی از ماژول‌های تحلیلی را انتخاب کنید.',
        };
    }


    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(Blade::render('<span class="relative -top-5 w-full !mb-0 !pb-0"><x-ui.title icon="analytics" title="تحلیل‌های ساختاری و سازمانی" count="5" countLabel="آیتم آماری" /></span>'));
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
                ['label' => 'وظایف باز', 'data' => $results->pluck('task_count')->toArray(), 'backgroundColor' => '#3b82f6'],
                ['label' => 'تیکت‌های باز', 'data' => $results->pluck('ticket_count')->toArray(), 'backgroundColor' => '#ef4444'],
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

        $genderMap = ['male' => 'آقا', 'female' => 'خانم'];
        $statusMap = ['active' => 'فعال', 'inactive' => 'غیرفعال', 'probation' => 'آزمایشی'];

        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $labels[] = ($genderMap[$row->gender] ?? 'نامشخص') . ' - ' . ($statusMap[$row->employment_status] ?? 'نامشخص');
            $data[] = $row->count;
        }

        return [
            'datasets' => [
                ['label' => 'بافت جمعیتی', 'data' => $data, 'backgroundColor' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b']],
            ],
            'labels' => $labels,
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function getModuleGData(string $departmentCode): array
    {
        $results = DB::table('posts')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(id) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->limit(30)
            ->get();

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
                ['label' => 'پست‌های منتشر شده', 'data' => $data, 'borderColor' => '#0ea5e9', 'backgroundColor' => 'rgba(14, 165, 233, 0.2)', 'fill' => true],
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
                ['label' => 'پرسنل', 'data' => $results->pluck('users_count')->toArray(), 'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16, 185, 129, 0.2)'],
                ['label' => 'گزارشات', 'data' => $results->pluck('reports_count')->toArray(), 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239, 68, 68, 0.2)'],
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
                ['label' => 'تعداد', 'data' => [$adsCount, $onboardingCount], 'backgroundColor' => ['#3b82f6', '#f59e0b']],
            ],
            'labels' => ['آگهی‌های فعال', 'آنبوردینگ‌های در جریان'],
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

        if ($module === 'module_f' || $module === 'module_h' || $module === 'module_i') {
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
            'module_i' => 'pie',
            default => 'bar',
        };
    }

    private function getScopeCondition(): string
    {
        return auth()->user()->profile?->department_id ?? '';
    }
}
