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
    protected ?string $heading = 'نمودارهای تحلیلی سیستم';
    protected static bool $isLazy = true;
    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        return [
            'module_a' => 'شاخص پیش‌بینی فرسودگی سرمایه انسانی',
            'module_b' => 'شاخص اصطکاک و تاخیر بین واحدها',
            'module_c' => 'قیف پیشرفت نوآوری و پیشنهادات',
            'module_d' => 'پراکندگی و تراکم استفاده از منابع',
            'module_e' => 'توزیع بار کاری (وظایف و تیکت‌ها)',
            'module_f' => 'ترکیب جمعیتی و وضعیت اشتغال',
            'module_g' => 'روند تعاملات و تولید محتوا',
            'module_h' => 'تراکم گزارشات و نظارت پذیری',
            'module_i' => 'قیف جذب و آنبوردینگ نیروها',
        ];
    }

    public function getDescription(): ?string
    {
        if (!$this->filter) {
            return 'لطفاً برای مشاهده آمار دقیق، یکی از ماژول‌های تحلیلی را از منوی بالا انتخاب کنید.';
        }

        return match ($this->filter) {
            'module_a' => 'این نمودار ارتباط بین میزان انرژی ثبت شده پرسنل و حجم وظایف باز در هر واحد را نشان می‌دهد. افت انرژی همزمان با افزایش وظایف، زنگ خطر فرسودگی است.',
            'module_b' => 'نمایشگر زمان صرف شده برای حل تیکت‌ها بین واحدهای مختلف. رنگ‌های متمایل به قرمز نشان‌دهنده گلوگاه‌های ارتباطی و تاخیر در پاسخگویی است.',
            'module_c' => 'نمایش وضعیت فعلی ایده‌ها و پیشنهادات ثبت شده در مراحل مختلف تایید تا اجرا، جهت بررسی سرعت چرخه نوآوری سازمان.',
            'module_d' => 'نمایش ساعات اوج استفاده از منابع و اتاق‌های جلسات در طول شبانه‌روز، جهت بهینه‌سازی مصرف انرژی و زمان‌بندی بهتر.',
            'module_e' => 'مقایسه حجم تیکت‌های باز (درخواست‌ها) و وظایف در حال انجام به تفکیک هر واحد، جهت شناسایی واحدهای دارای بار کاری نامتوازن.',
            'module_f' => 'بررسی ساختار جمعیتی پرسنل شامل جنسیت و وضعیت قراردادها جهت تصمیم‌گیری بهتر در سیاست‌های منابع انسانی.',
            'module_g' => 'روند انتشار پست‌ها و اخبار در ۳۰ روز گذشته که نشان‌دهنده میزان پویایی و ارتباطات داخلی سازمان است.',
            'module_h' => 'نسبت کاربران فعال به تعداد گزارشات ثبت شده در هر واحد، که نشانگر سطح قانون‌مداری و شفافیت عملکردی است.',
            'module_i' => 'مقایسه تعداد آگهی‌های استخدامی فعال با تعداد پرونده‌های در جریان آنبوردینگ، جهت سنجش سرعت فرآیند جذب استعدادها.',
            default => '',
        };
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
        $fontFamily = 'Yekan Bakh, Yekan, Tahoma, sans-serif';

        $baseOptions = [
            'plugins' => [
                'legend' => [
                    'labels' => [
                        'font' => ['family' => $fontFamily]
                    ]
                ]
            ],
            'scales' => [
                'x' => [
                    'ticks' => ['font' => ['family' => $fontFamily]]
                ],
                'y' => [
                    'ticks' => ['font' => ['family' => $fontFamily]]
                ]
            ]
        ];

        if (in_array($this->filter, ['module_c', 'module_i'])) {
            $baseOptions['indexAxis'] = 'y';
            return $baseOptions;
        }

        if ($this->filter === 'module_d') {
             $baseOptions['scales']['y']['beginAtZero'] = true;
             $baseOptions['plugins']['filler'] = ['propagate' => false];
             return $baseOptions;
        }

        if ($this->filter === 'module_e') {
            $baseOptions['scales']['x']['stacked'] = true;
            $baseOptions['scales']['y']['stacked'] = true;
            return $baseOptions;
        }

        if ($this->filter === 'module_f' || $this->filter === 'module_h') {
             unset($baseOptions['scales']);
             return $baseOptions;
        }

        return $baseOptions;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        if ($this->filter === 'module_b') {
            return view('livewire.filament.widgets.module-analytics-charts-friction', [
                'frictionData' => $this->getModuleBData($this->getScopeCondition()),
                'filter' => $this->filter,
                'description' => $this->getDescription()
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

        $stageTranslations = [
            'pending' => 'در انتظار بررسی',
            'team_remarks' => 'نظرات تیم',
            'dept_remarks' => 'نظرات واحد',
            'awaiting_decision' => 'در انتظار تصمیم',
            'accepted' => 'تایید شده',
            'rejected' => 'رد شده',
            'under_review' => 'در حال بررسی',
            'closed' => 'بسته شده'
        ];

        $stages = array_keys($stageTranslations);
        $data = [];
        $labels = [];

        foreach ($stages as $stage) {
            $data[] = $results[$stage] ?? 0;
            $labels[] = $stageTranslations[$stage];
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
            $g = $genderMap[$row->gender] ?? 'نامشخص';
            $s = $statusMap[$row->employment_status] ?? 'نامشخص';
            $labels[] = $g . ' - ' . $s;
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
}
