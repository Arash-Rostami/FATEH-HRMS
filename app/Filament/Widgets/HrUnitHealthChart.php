<?php

namespace App\Filament\Widgets;

use App\Services\HrAnalyticsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class HrUnitHealthChart extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = true;
    protected static ?int $sort = 3;
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
                    'hr_i' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_i'),
                    'hr_j' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_j'),
                    'hr_k' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_k'),
                    'hr_l' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_l'),
                    'hr_m' => __('resources/dashboard/strings.hr_analytics.unit_health.modules.hr_m'),
                ]),
        ]);
    }

    public function getDescription(): ?string
    {
        return match ($this->activeModule()) {
            'hr_i' => __('resources/dashboard/strings.hr_analytics.unit_health.descriptions.hr_i'),
            'hr_j' => __('resources/dashboard/strings.hr_analytics.unit_health.descriptions.hr_j'),
            'hr_k' => __('resources/dashboard/strings.hr_analytics.unit_health.descriptions.hr_k'),
            'hr_l' => __('resources/dashboard/strings.hr_analytics.unit_health.descriptions.hr_l'),
            'hr_m' => __('resources/dashboard/strings.hr_analytics.unit_health.descriptions.hr_m'),
            default => __('resources/dashboard/strings.chart_widgets.default_description'),
        };
    }

    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(Blade::render(
            '<span class="relative -top-5 w-full !mb-0 !pb-0"><x-ui.title icon="analytics" :title="$title" count="5" countLabel="آیتم آماری" /></span>',
            ['title' => __('resources/dashboard/strings.hr_analytics.unit_health.label')]
        ));
    }

    public function getHrIData(): array
    {
        return app(HrAnalyticsService::class)->getHrIData();
    }

    public function getHrJData(): array
    {
        return app(HrAnalyticsService::class)->getHrJData();
    }

    public function getHrKData(): array
    {
        return app(HrAnalyticsService::class)->getHrKData();
    }

    public function getHrLData(): array
    {
        return app(HrAnalyticsService::class)->getHrLData();
    }

    public function getHrMData(): array
    {
        return app(HrAnalyticsService::class)->getHrMData();
    }

    protected function activeModule(): ?string
    {
        return $this->filters['module'] ?? null;
    }

    protected function getData(): array
    {
        return match ($this->activeModule()) {
            'hr_i' => $this->getHrIData(),
            'hr_j' => $this->getHrJData(),
            'hr_k' => $this->getHrKData(),
            'hr_l' => $this->getHrLData(),
            'hr_m' => $this->getHrMData(),
            default => ['datasets' => [], 'labels' => []],
        };
    }

    protected function getOptions(): array
    {
        $fontFamily = 'Yekan Bakh, Yekan, Tahoma, sans-serif';
        $base = [
            'plugins' => [
                'legend' => ['labels' => ['font' => ['family' => $fontFamily]]],
            ],
            'scales' => [
                'x' => ['ticks' => ['font' => ['family' => $fontFamily]], 'stacked' => true],
                'y' => ['ticks' => ['font' => ['family' => $fontFamily]], 'stacked' => true],
            ],
        ];

        $module = $this->activeModule();

        if ($module === 'hr_i') {
            $base['indexAxis'] = 'y';
            $base['scales']['x']['beginAtZero'] = true;
            return $base;
        }

        if ($module === 'hr_m') {
            $base['scales']['x']['stacked'] = false;
            $base['scales']['y']['stacked'] = false;
            $base['scales']['x']['beginAtZero'] = true;
            $base['scales']['x']['max'] = 100;
            return $base;
        }

        return $base;
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
