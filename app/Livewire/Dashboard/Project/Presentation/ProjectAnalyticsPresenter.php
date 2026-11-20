<?php

namespace App\Livewire\Dashboard\Project\Presentation;

use App\Services\ProjectTask\TaskMetrics;

class ProjectAnalyticsPresenter
{
    private const CHART_TAB = [
        'throughput' => 'flow',
        'staleDistribution' => 'flow',
        'cycleDistribution' => 'flow',
        'atRiskByPriority' => 'risk',
        'horizon' => 'risk',
        'perAssignee' => 'people',
        'doneByAssignee' => 'people',
        'labelsDistribution' => 'people',
        'departmentCompletion' => 'people',
    ];

    private const CHART_DRILL_PARAM = [
        'atRiskByPriority' => 'priority',
        'horizon' => 'deadline',
        'perAssignee' => 'assignee',
        'doneByAssignee' => 'assignee',
        'labelsDistribution' => 'label',
        'departmentCompletion' => 'department',
    ];

    public function drillParamMap(): array
    {
        return self::CHART_DRILL_PARAM;
    }

    public function tabs(): array
    {
        return [
            'flow' => [
                'label' => 'جریان کار',
                'icon' => 'waterfall_chart',
                'charts' => ['throughput', 'staleDistribution', 'cycleDistribution'],
            ],
            'risk' => [
                'label' => 'ریسک و مهلت',
                'icon' => 'crisis_alert',
                'charts' => ['atRiskByPriority', 'horizon'],
            ],
            'people' => [
                'label' => 'کارهای اعضا',
                'icon' => 'group',
                'charts' => ['perAssignee', 'doneByAssignee', 'labelsDistribution', 'departmentCompletion'],
            ],
        ];
    }

    public function chartConfig(string $chartKey): array
    {
        return match ($chartKey) {
            'throughput' => [
                'type' => 'line',
                'label' => 'کارهای انجام‌شده در هر هفته',
                'description' => 'نشان می‌دهد در هر هفته چند وظیفه انجام شده است.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => [], 'y' => ['beginAtZero' => true]],
                ],
            ],
            'staleDistribution' => [
                'type' => 'bar',
                'label' => 'مدت بی‌تغییر ماندن کارها',
                'description' => 'نشان می‌دهد از آخرین تغییر هر وظیفهٔ ناتمام چقدر گذشته است؛ برای پیدا کردن کارهای نیازمند پیگیری.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => ['beginAtZero' => true], 'y' => []],
                ],
            ],
            'cycleDistribution' => [
                'type' => 'bar',
                'label' => 'توزیع مدت انجام',
                'description' => 'وظایف انجام‌شده را بر اساس زمان بین ثبت و انجام دسته‌بندی می‌کند؛ ستون‌های زمان طولانی‌تر، کارهای زمان‌بر را نشان می‌دهند.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => ['beginAtZero' => true], 'y' => []],
                ],
            ],
            'atRiskByPriority' => [
                'type' => 'doughnut',
                'label' => 'سررسید نزدیک بر اساس اولویت',
                'description' => 'وظایف ناتمام با مهلت در ۳ روز آینده، گروه‌بندی‌شده بر اساس اولویت.',
                'options' => ['plugins' => ['legend' => ['display' => true]]],
            ],
            'horizon' => [
                'type' => 'bar',
                'label' => 'چقدر تا مهلت کارها مانده؟',
                'description' => 'کارهای ناتمام را بر اساس زمان باقی‌مانده تا مهلت جدا می‌کند. «بدون مهلت» یعنی تاریخی برای انجام کار ثبت نشده است.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => ['beginAtZero' => true], 'y' => []],
                ],
            ],
            'perAssignee' => [
                'type' => 'bar',
                'label' => 'کارهای ناتمام هر مسئول',
                'description' => 'تعداد کارهای هر مسئول را به تفکیک وضعیت نشان می‌دهد؛ این تعداد، زمان یا دشواری کارها را اندازه نمی‌گیرد.',
                'options' => [
                    'plugins' => ['legend' => ['display' => true]],
                    'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'beginAtZero' => true]],
                ],
            ],
            'doneByAssignee' => [
                'type' => 'bar',
                'label' => 'کارهای انجام‌شدهٔ هر مسئول',
                'description' => 'تعداد کارهای انجام‌شدهٔ هر مسئول را نشان می‌دهد. کم بودن کارهای باز به‌تنهایی به معنی کم‌کاری نیست.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => ['beginAtZero' => true], 'y' => []],
                ],
            ],
            'labelsDistribution' => [
                'type' => 'doughnut',
                'label' => 'پراکندگی برچسب‌ها',
                'description' => 'برچسب‌هایی که بیشتر روی وظایف این پروژه استفاده شده‌اند.',
                'options' => ['plugins' => ['legend' => ['display' => true]]],
            ],
            'departmentCompletion' => [
                'type' => 'bar',
                'label' => 'پیشرفت هر بخش سازمانی',
                'description' => 'تعداد کارهای انجام‌شده و باقی‌ماندهٔ هر بخش سازمانی را مقایسه می‌کند.',
                'options' => [
                    'plugins' => ['legend' => ['display' => true]],
                    'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'beginAtZero' => true]],
                ],
            ],
            default => ['type' => 'bar', 'label' => '', 'description' => '', 'options' => []],
        };
    }

    public function chartConfigMap(): array
    {
        $map = [];
        foreach (array_keys(self::CHART_TAB) as $chartKey) {
            $map[$chartKey] = $this->chartConfig($chartKey);
        }

        return $map;
    }

    public function analyticsBoot(array $insights): array
    {
        $tabs = $this->tabs();

        $jsChartData = [];
        foreach ($tabs as $tab) {
            foreach ($tab['charts'] as $chartKey) {
                $jsChartData[$chartKey] = $this->chartData($insights, $chartKey);
            }
        }

        return [
            'tabs' => $tabs,
            'jsChartData' => $jsChartData,
            'verdicts' => $this->verdicts($insights),
            'matrix' => $this->matrix($insights['risk'] ?? []),
        ];
    }

    public function heatmapCell(array $matrix, string $priorityValue, string $statusValue): array
    {
        $count = 0;
        foreach ($matrix['priorities'] as $i => $p) {
            if ($p['value'] !== $priorityValue) {
                continue;
            }
            foreach ($matrix['statuses'] as $j => $s) {
                if ($s['value'] === $statusValue) {
                    $count = $matrix['cells'][$i][$j] ?? 0;
                }
            }
        }

        return [
            'count' => $count,
            'isFire' => in_array($priorityValue . '|' . $statusValue, array_map(fn ($f) => $f[0] . '|' . $f[1], $matrix['fire'] ?? [])),
            'intensity' => $matrix['max'] > 0 ? (int) round($count / $matrix['max'] * 100) : 0,
        ];
    }

    public function chartTone(string $tone): string
    {
        return match ($tone) {
            'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
            default => 'bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)]',
        };
    }

    public function chartData(array $insights, string $chartKey): ?array
    {
        $tab = self::CHART_TAB[$chartKey] ?? null;
        if (!$tab) {
            return null;
        }

        return $insights[$tab][$chartKey] ?? null;
    }

    public function matrix(array $risk): array
    {
        $m = $risk['matrix'] ?? null;
        if (!$m) {
            return ['priorities' => [], 'statuses' => [], 'cells' => [], 'fire' => [], 'max' => 0];
        }
        $max = 0;
        foreach ($m['cells'] as $row) {
            foreach ($row as $count) {
                $max = max($max, $count);
            }
        }

        return [
            'priorities' => $m['priorities'],
            'statuses' => $m['statuses'],
            'cells' => $m['cells'],
            'fire' => $m['fire'],
            'max' => $max,
        ];
    }

    public function kpis(string $tabKey, array $insights): array
    {
        return match ($tabKey) {
            'flow' => $this->flowKpis($insights['flow'] ?? []),
            'risk' => $this->riskKpis($insights['risk'] ?? []),
            'people' => $this->peopleKpis($insights['people'] ?? []),
            default => [],
        };
    }

    public function verdicts(array $insights): array
    {
        $flow = $insights['flow'] ?? [];
        $risk = $insights['risk'] ?? [];
        $people = $insights['people'] ?? [];

        $flowVerdict = [];
        if (($flow['medianCycle'] ?? null) !== null) {
            $flowVerdict[] = 'زمان معمول انجام کارها ' . $flow['medianCycle'] . ' روز است.';
        }
        $idle = (int) ($flow['stale']['idle_30_plus'] ?? 0);
        if ($idle > 0) {
            $flowVerdict[] = $idle . ' کار بیش از ۳۰ روز بی‌تغییر مانده‌اند.';
        }

        $riskVerdict = [];
        if (($risk['atRiskCount'] ?? 0) > 0) {
            $riskVerdict[] = $risk['atRiskCount'] . ' کار ' . TaskMetrics::LABEL_DUE_SOON . ' دارند.';
        }
        $fire = (int) ($risk['matrix']['fireCount'] ?? 0);
        if ($fire > 0) {
            $riskVerdict[] = $fire . ' کار فوری هنوز انجام نشده است.';
        }
        $adherence = $risk['adherence'] ?? null;
        if ($adherence !== null) {
            $riskVerdict[] = 'انجام به‌موقع: ' . $adherence['onTime'] . ' از ' . $adherence['total'] . '.';
        }

        $peopleVerdict = [];
        if (($people['orphanCount'] ?? 0) > 0) {
            $peopleVerdict[] = $people['orphanCount'] . ' کار هنوز مسئول انجام ندارند.';
        }
        $peopleVerdict[] = round($people['delegationRatio'] ?? 0) . '٪ کارها به فردی غیر از ثبت‌کننده سپرده شده‌اند.';

        return [
            'flow' => implode(' ', $flowVerdict) ?: 'هنوز دادهٔ کافی برای این جمع‌بندی ثبت نشده است.',
            'risk' => implode(' ', $riskVerdict) ?: 'در این بررسی، کار فوریِ ناتمام یا مهلت نزدیک دیده نمی‌شود.',
            'people' => implode(' ', $peopleVerdict),
        ];
    }

    private function flowKpis(array $flow): array
    {
        $kpis = [];
        if (($flow['medianCycle'] ?? null) !== null) {
            $kpis[] = ['label' => 'زمان معمول انجام', 'value' => $flow['medianCycle'] . ' روز', 'icon' => 'timer', 'tone' => 'primary'];
        }
        $kpis[] = ['label' => 'تکمیل این هفته', 'value' => (string) ($flow['doneThisWeek'] ?? 0), 'icon' => 'check_circle', 'tone' => 'tertiary'];
        $stale = $flow['stale'] ?? ['active' => 0, 'idle_7_14' => 0, 'idle_14_30' => 0, 'idle_30_plus' => 0];
        $kpis[] = ['label' => 'فعال (۷ روز)', 'value' => (string) $stale['active'], 'icon' => 'bolt', 'tone' => 'secondary'];
        if ($stale['idle_30_plus'] > 0) {
            $kpis[] = ['label' => 'بی‌تغییر +۳۰ روز', 'value' => (string) $stale['idle_30_plus'], 'icon' => 'pause_circle', 'tone' => 'error'];
        }

        return $kpis;
    }

    private function riskKpis(array $risk): array
    {
        $kpis = [];
        $kpis[] = ['label' => TaskMetrics::LABEL_DUE_SOON, 'value' => (string) ($risk['atRiskCount'] ?? 0), 'icon' => 'warning', 'tone' => 'error'];
        $adherence = $risk['adherence'] ?? null;
        if ($adherence !== null) {
            $kpis[] = ['label' => 'الزام به مهلت', 'value' => $adherence['onTime'] . ' از ' . $adherence['total'], 'icon' => 'verified', 'tone' => 'tertiary'];
        }
        $fire = (int) ($risk['matrix']['fireCount'] ?? 0);
        if ($fire > 0) {
            $kpis[] = ['label' => 'فوری ناتمام', 'value' => (string) $fire, 'icon' => 'local_fire_department', 'tone' => 'error'];
        }

        return $kpis;
    }

    private function peopleKpis(array $people): array
    {
        $kpis = [];
        $kpis[] = ['label' => 'نسبت محول‌کردن', 'value' => round($people['delegationRatio'] ?? 0) . '٪', 'icon' => 'forward_to_inbox', 'tone' => 'secondary'];
        if (($people['orphanCount'] ?? 0) > 0) {
            $kpis[] = ['label' => 'کار بی‌مسئول', 'value' => (string) $people['orphanCount'], 'icon' => 'help', 'tone' => 'error'];
        }

        return $kpis;
    }
}
