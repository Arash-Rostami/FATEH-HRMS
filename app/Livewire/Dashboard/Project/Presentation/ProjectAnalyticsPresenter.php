<?php

namespace App\Livewire\Dashboard\Project\Presentation;

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
                'label' => 'بار افراد',
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
                'label' => 'ریتم تکمیل هفتگی',
                'description' => 'تعداد وظایف تکمیل‌شده در هر هفته — نوسان ریتم پروژه را نشان می‌دهد.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => [], 'y' => ['beginAtZero' => true]],
                ],
            ],
            'staleDistribution' => [
                'type' => 'bar',
                'label' => 'توزیع رکود کار',
                'description' => 'کارهای ناتمام بر اساس آخرین تغییر — میزان چسبیدن و رهاشدن کارها در جریان.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => ['beginAtZero' => true], 'y' => []],
                ],
            ],
            'cycleDistribution' => [
                'type' => 'bar',
                'label' => 'توزیع مدت انجام',
                'description' => 'وظایف تکمیل‌شده بر اساس مدت از ایجاد تا انجام — دنبالهٔ سنگین یعنی گلوگاه در جریان.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => ['beginAtZero' => true], 'y' => []],
                ],
            ],
            'atRiskByPriority' => [
                'type' => 'doughnut',
                'label' => 'کارهای در معرض ریسک بر اساس اولویت',
                'description' => 'وظایف ناتمام با مهلت سررسید یا نزدیک به سررسید، گروه‌بندی‌شده بر اساس اولویت.',
                'options' => ['plugins' => ['legend' => ['display' => true]]],
            ],
            'horizon' => [
                'type' => 'bar',
                'label' => 'افق مهلت‌ها',
                'description' => 'توزیع وظایف ناتمام بر اساس فاصلهٔ مهلت — بخش «بدون مهلت» شکاف برنامه‌ریزی است.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => ['beginAtZero' => true], 'y' => []],
                ],
            ],
            'perAssignee' => [
                'type' => 'bar',
                'label' => 'بار کار روی هر مسئول',
                'description' => 'وظایف باز هر فرد، تجمیعی بر اساس وضعیت — توزیع فشار کار در تیم.',
                'options' => [
                    'plugins' => ['legend' => ['display' => true]],
                    'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'beginAtZero' => true]],
                ],
            ],
            'doneByAssignee' => [
                'type' => 'bar',
                'label' => 'تکمیل به نام مسئول',
                'description' => 'تعداد وظایف انجام‌شدهٔ هر مسئول — مکمل بار باز؛ خالی‌بودنِ بار لزوماً بیکاری نیست.',
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => ['beginAtZero' => true], 'y' => []],
                ],
            ],
            'labelsDistribution' => [
                'type' => 'doughnut',
                'label' => 'پراکندگی برچسب‌ها',
                'description' => 'تکراربرترین برچسب‌های روی وظایف پروژه.',
                'options' => ['plugins' => ['legend' => ['display' => true]]],
            ],
            'departmentCompletion' => [
                'type' => 'bar',
                'label' => 'پیشرفت بر اساس دپارتمان',
                'description' => 'نسبت وظایف انجام‌شده به باقی‌مانده در هر دپارتمان.',
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
            $flowVerdict[] = 'میانگین چرخهٔ انجام ' . $flow['medianCycle'] . ' روز است.';
        }
        $idle = (int) ($flow['stale']['idle_30_plus'] ?? 0);
        if ($idle > 0) {
            $flowVerdict[] = $idle . ' کار بیش از ۳۰ روز بی‌تغیر مانده‌اند.';
        }

        $riskVerdict = [];
        if (($risk['atRiskCount'] ?? 0) > 0) {
            $riskVerdict[] = $risk['atRiskCount'] . ' کار در معرض ریسک سررسید هستند.';
        }
        $fire = count($risk['matrix']['fire'] ?? []);
        if ($fire > 0) {
            $riskVerdict[] = $fire . ' کار فوری هنوز شروع/در حال انجام است.';
        }
        $adherence = $risk['adherence'] ?? null;
        if ($adherence !== null) {
            $riskVerdict[] = 'الزام به مهلت: ' . $adherence['onTime'] . ' از ' . $adherence['total'] . '.';
        }

        $peopleVerdict = [];
        if (($people['orphanCount'] ?? 0) > 0) {
            $peopleVerdict[] = $people['orphanCount'] . ' کار بی‌مسئول رها شده‌اند.';
        }
        $peopleVerdict[] = round($people['delegationRatio'] ?? 0) . '٪ کارها محول‌شده به غیر هستند.';

        return [
            'flow' => implode(' ', $flowVerdict) ?: 'پروژه در جریان است.',
            'risk' => implode(' ', $riskVerdict) ?: 'هیچ ریسک سررسید فعالی نیست.',
            'people' => implode(' ', $peopleVerdict),
        ];
    }

    private function flowKpis(array $flow): array
    {
        $kpis = [];
        if (($flow['medianCycle'] ?? null) !== null) {
            $kpis[] = ['label' => 'میانگین چرخه', 'value' => $flow['medianCycle'] . ' روز', 'icon' => 'timer', 'tone' => 'primary'];
        }
        $kpis[] = ['label' => 'تکمیل این هفته', 'value' => (string) ($flow['doneThisWeek'] ?? 0), 'icon' => 'check_circle', 'tone' => 'tertiary'];
        $stale = $flow['stale'] ?? ['active' => 0, 'idle_7_14' => 0, 'idle_14_30' => 0, 'idle_30_plus' => 0];
        $kpis[] = ['label' => 'فعال (۷ روز)', 'value' => (string) $stale['active'], 'icon' => 'bolt', 'tone' => 'secondary'];
        if ($stale['idle_30_plus'] > 0) {
            $kpis[] = ['label' => 'بی‌تغیر +۳۰ روز', 'value' => (string) $stale['idle_30_plus'], 'icon' => 'pause_circle', 'tone' => 'error'];
        }

        return $kpis;
    }

    private function riskKpis(array $risk): array
    {
        $kpis = [];
        $kpis[] = ['label' => 'در معرض ریسک', 'value' => (string) ($risk['atRiskCount'] ?? 0), 'icon' => 'warning', 'tone' => 'error'];
        $adherence = $risk['adherence'] ?? null;
        if ($adherence !== null) {
            $kpis[] = ['label' => 'الزام به مهلت', 'value' => $adherence['onTime'] . ' از ' . $adherence['total'], 'icon' => 'verified', 'tone' => 'tertiary'];
        }
        $fire = count($risk['matrix']['fire'] ?? []);
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