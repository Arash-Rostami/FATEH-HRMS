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
                    'module_i' => 'توزیع سنوات خدمت کارکنان',
                ]),
        ]);
    }

    public function getDescription(): ?string
    {
        return match ($this->activeModule()) {
            'module_e' => 'از تسک بورد (وظایف باز «مسئول انجام» هر کارمند) و سیستم تیکت (تیکت‌های باز «مسئول رسیدگی» هر کارمند) داده گرفته و بر اساس «واحد» سازمانی آن کارمند گروه‌بندی می‌شود. '
                . 'واحدی که در هر دو ستون عدد بالایی دارد زیر بار سنگین کار است و نیاز به توجه فوری دارد. '
                . '(منابع: تسک بورد ← «وضعیت»، «مسئول انجام» | تیکت‌ها ← «وضعیت»، «مسئول رسیدگی» | پروفایل پرسنلی ← «واحد»)',

            'module_f' => 'از پروفایل پرسنلی، «جنسیت» و «وضعیت استخدام» کنار هم گروه‌بندی می‌شوند. '
                . 'تصویری واقعی از ترکیب نیروی کار می‌دهد که پایه تصمیم‌گیری برای سیاست‌های استخدام و تنوع است. '
                . '(منابع: پروفایل پرسنلی ← «جنسیت»، «وضعیت استخدام»)',

            'module_g' => 'از ماژول پست و اعلانات، «تاریخ انتشار» هر پست گرفته و تعداد پست‌های روزانه در ۳۰ روز گذشته نمایش داده می‌شود. روزهایی که هیچ پستی نبوده صفر نشان می‌دهد. '
                . 'کاهش ناگهانی یعنی ارتباطات داخلی سازمان کند شده است. '
                . '(منابع: پست و اعلانات ← «تاریخ انتشار»)',

            'module_h' => 'از پروفایل پرسنلی تعداد کارکنان هر «واحد» و از ماژول گزارشات تعداد گزارش‌های ثبت‌شده توسط «کاربر» همان واحد گرفته می‌شود. '
                . 'واحدی که با وجود پرسنل زیاد گزارش کمی دارد از نظر شفافیت و مستندسازی عملکرد ضعیف است. '
                . '(منابع: پروفایل پرسنلی ← «واحد» | گزارش‌ها ← «کاربر»، «واحد سازمانی»)',

            'module_i' => 'از پروفایل پرسنلی، «تاریخ شروع» هر کارمندی که «وضعیت استخدام» او خاتمه‌یافته نیست گرفته و سنوات خدمت محاسبه می‌شود، سپس در پنج دسته قرار می‌گیرد: کمتر از ۱ سال، ۱ تا ۳، ۳ تا ۵، ۵ تا ۱۰، و بیش از ۱۰ سال. '
                . 'اگر بیشتر نیرو در دسته‌های اول باشند دانش سازمانی شکننده است و ریسک خروج بالاست. '
                . '(منابع: پروفایل پرسنلی ← «تاریخ شروع»، «وضعیت استخدام»)',

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
                ['label' => 'اعلانات منتشر شده', 'data' => $data, 'borderColor' => '#0ea5e9', 'backgroundColor' => 'rgba(14, 165, 233, 0.2)', 'fill' => true],
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
        $query = DB::table('profiles')
            ->whereNotNull('start_date')
            ->whereNotIn('employment_status', ['terminated']);

        if ($departmentCode) {
            $query->where('department_id', $departmentCode);
        }

        $startDates = $query->pluck('start_date');

        $buckets = ['کمتر از ۱ سال' => 0, '۱ تا ۳ سال' => 0, '۳ تا ۵ سال' => 0, '۵ تا ۱۰ سال' => 0, 'بیش از ۱۰ سال' => 0];
        foreach ($startDates as $sd) {
            $years = Carbon::parse($sd)->diffInYears(now());
            if ($years < 1)       $buckets['کمتر از ۱ سال']++;
            elseif ($years < 3)   $buckets['۱ تا ۳ سال']++;
            elseif ($years < 5)   $buckets['۳ تا ۵ سال']++;
            elseif ($years < 10)  $buckets['۵ تا ۱۰ سال']++;
            else                  $buckets['بیش از ۱۰ سال']++;
        }

        return [
            'datasets' => [
                ['label' => 'تعداد کارکنان', 'data' => array_values($buckets), 'backgroundColor' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']],
            ],
            'labels' => array_keys($buckets),
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
