<?php

namespace App\Livewire\Dashboard\Analytics\Presentation;

class AnalyticsPresenter
{
    private const FONT = ['family' => 'Yekan, system-ui'];

    public function categories(): array
    {
        return [
            'demographics' => [
                'label' => __('resources/dashboard/strings.hr_analytics.demographics.label'),
                'icon' => 'group',
                'modules' => [
                    'hr_a' => __('resources/dashboard/strings.hr_analytics.demographics.modules.hr_a'),
                    'hr_b' => __('resources/dashboard/strings.hr_analytics.demographics.modules.hr_b'),
                    'hr_c' => __('resources/dashboard/strings.hr_analytics.demographics.modules.hr_c'),
                    'hr_d' => __('resources/dashboard/strings.hr_analytics.demographics.modules.hr_d'),
                ],
            ],
            'qualification' => [
                'label' => __('resources/dashboard/strings.hr_analytics.qualification.label'),
                'icon' => 'school',
                'modules' => [
                    'hr_e' => __('resources/dashboard/strings.hr_analytics.qualification.modules.hr_e'),
                    'hr_f' => __('resources/dashboard/strings.hr_analytics.qualification.modules.hr_f'),
                    'hr_g' => __('resources/dashboard/strings.hr_analytics.qualification.modules.hr_g'),
                    'hr_h' => __('resources/dashboard/strings.hr_analytics.qualification.modules.hr_h'),
                ],
            ],
            'unitHealth' => [
                'label' => __('resources/dashboard/strings.hr_analytics.unit_health.label'),
                'icon' => 'apartment',
                'modules' => [
                    'hr_i' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_i'),
                    'hr_j' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_j'),
                    'hr_k' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_k'),
                    'hr_l' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_l'),
                    'hr_m' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_m'),
                ],
            ],
            'engagement' => [
                'label' => __('resources/dashboard/strings.hr_analytics.engagement.label'),
                'icon' => 'diversity_3',
                'modules' => [
                    'hr_n' => __('resources/dashboard/strings.hr_analytics.engagement.modules.hr_n'),
                    'hr_o' => __('resources/dashboard/strings.hr_analytics.engagement.modules.hr_o'),
                    'hr_p' => __('resources/dashboard/strings.hr_analytics.engagement.modules.hr_p'),
                    'hr_q' => __('resources/dashboard/strings.hr_analytics.engagement.modules.hr_q'),
                ],
            ],
        ];
    }

    public function description(string $moduleKey): string
    {
        $category = match ($moduleKey) {
            'hr_a', 'hr_b', 'hr_c', 'hr_d' => 'demographics',
            'hr_e', 'hr_f', 'hr_g', 'hr_h' => 'qualification',
            'hr_i', 'hr_j', 'hr_k', 'hr_l', 'hr_m' => 'unit_health',
            'hr_n', 'hr_o', 'hr_p', 'hr_q' => 'engagement',
            default => null,
        };

        return $category === null
            ? __('resources/dashboard/strings.chart_widgets.default_description')
            : __("resources/dashboard/strings.hr_analytics.{$category}.descriptions.{$moduleKey}");
    }

    public function chartType(string $moduleKey): string
    {
        return $moduleKey === 'hr_d' ? 'doughnut' : 'bar';
    }

    public function chartOptions(string $moduleKey): array
    {
        return match (true) {
            in_array($moduleKey, ['hr_a', 'hr_b', 'hr_c', 'hr_d'], true) => $this->demographicsOptions($moduleKey),
            in_array($moduleKey, ['hr_e', 'hr_f', 'hr_g', 'hr_h'], true) => $this->qualificationOptions($moduleKey),
            in_array($moduleKey, ['hr_i', 'hr_j', 'hr_k', 'hr_l', 'hr_m'], true) => $this->unitHealthOptions($moduleKey),
            default => $this->engagementOptions($moduleKey),
        };
    }

    public function chartConfig(): array
    {
        $config = [];

        foreach ($this->categories() as $category) {
            foreach ($category['modules'] as $moduleKey => $label) {
                $config[$moduleKey] = [
                    'type' => $this->chartType($moduleKey),
                    'options' => $this->chartOptions($moduleKey),
                    'label' => $label,
                    'description' => $this->description($moduleKey),
                ];
            }
        }

        return $config;
    }

    private function demographicsOptions(string $moduleKey): array
    {
        $base = $this->baseOptions();

        if ($moduleKey === 'hr_d') {
            unset($base['scales']);
            return $base;
        }

        if ($moduleKey === 'hr_a') {
            $base['scales']['x']['stacked'] = true;
            $base['scales']['y']['stacked'] = true;
        }

        return $base;
    }

    private function qualificationOptions(string $moduleKey): array
    {
        $base = $this->baseOptions();
        $base['scales']['x']['stacked'] = true;
        $base['scales']['y']['stacked'] = true;

        if ($moduleKey === 'hr_f') {
            $base['scales']['x']['stacked'] = false;
            $base['scales']['y']['stacked'] = false;
        }

        return $base;
    }

    private function unitHealthOptions(string $moduleKey): array
    {
        $base = $this->baseOptions();
        $base['scales']['x']['stacked'] = true;
        $base['scales']['y']['stacked'] = true;

        if ($moduleKey === 'hr_i') {
            $base['indexAxis'] = 'y';
            $base['scales']['x']['beginAtZero'] = true;
            return $base;
        }

        if ($moduleKey === 'hr_m') {
            $base['scales']['x']['stacked'] = false;
            $base['scales']['y']['stacked'] = false;
            $base['scales']['x']['beginAtZero'] = true;
            $base['scales']['x']['max'] = 100;
        }

        return $base;
    }

    private function engagementOptions(string $moduleKey): array
    {
        $base = $this->baseOptions();
        $base['scales']['x']['stacked'] = true;
        $base['scales']['x']['beginAtZero'] = true;
        $base['scales']['y']['stacked'] = true;

        if (in_array($moduleKey, ['hr_o', 'hr_p', 'hr_q'], true)) {
            $base['scales']['x']['stacked'] = false;
            $base['scales']['y']['stacked'] = false;
        }

        return $base;
    }

    private function baseOptions(): array
    {
        return [
            'plugins' => ['legend' => ['labels' => ['font' => self::FONT]]],
            'scales' => [
                'x' => ['ticks' => ['font' => self::FONT]],
                'y' => ['ticks' => ['font' => self::FONT]],
            ],
        ];
    }
}
