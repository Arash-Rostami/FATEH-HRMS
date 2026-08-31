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

class HrDemographicsChart extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = true;
    protected static ?int $sort = 1;
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
                    'hr_a' => __('resources/dashboard/strings.hr_analytics.demographics.modules.hr_a'),
                    'hr_b' => __('resources/dashboard/strings.hr_analytics.demographics.modules.hr_b'),
                    'hr_c' => __('resources/dashboard/strings.hr_analytics.demographics.modules.hr_c'),
                    'hr_d' => __('resources/dashboard/strings.hr_analytics.demographics.modules.hr_d'),
                ]),
        ]);
    }

    public function getDescription(): ?string
    {
        return match ($this->activeModule()) {
            'hr_a' => __('resources/dashboard/strings.hr_analytics.demographics.descriptions.hr_a'),
            'hr_b' => __('resources/dashboard/strings.hr_analytics.demographics.descriptions.hr_b'),
            'hr_c' => __('resources/dashboard/strings.hr_analytics.demographics.descriptions.hr_c'),
            'hr_d' => __('resources/dashboard/strings.hr_analytics.demographics.descriptions.hr_d'),
            default => __('resources/dashboard/strings.chart_widgets.default_description'),
        };
    }

    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(Blade::render(
            '<span class="relative -top-5 w-full !mb-0 !pb-0"><x-ui.title icon="analytics" :title="$title" count="4" countLabel="آیتم آماری" /></span>',
            ['title' => __('resources/dashboard/strings.hr_analytics.demographics.label')]
        ));
    }

    public function getHrAData(): array
    {
        return app(HrAnalyticsService::class)->getHrAData();
    }

    public function getHrBData(): array
    {
        return app(HrAnalyticsService::class)->getHrBData();
    }

    public function getHrCData(): array
    {
        return app(HrAnalyticsService::class)->getHrCData();
    }

    public function getHrDData(): array
    {
        return app(HrAnalyticsService::class)->getHrDData();
    }

    protected function activeModule(): ?string
    {
        return $this->filters['module'] ?? null;
    }

    protected function getData(): array
    {
        return match ($this->activeModule()) {
            'hr_a' => $this->getHrAData(),
            'hr_b' => $this->getHrBData(),
            'hr_c' => $this->getHrCData(),
            'hr_d' => $this->getHrDData(),
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
                'x' => ['ticks' => ['font' => ['family' => $fontFamily]]],
                'y' => ['ticks' => ['font' => ['family' => $fontFamily]]],
            ],
        ];

        $module = $this->activeModule();

        if ($module === 'hr_d') {
            unset($base['scales']);
            return $base;
        }

        if ($module === 'hr_a') {
            $base['scales']['x']['stacked'] = true;
            $base['scales']['y']['stacked'] = true;
        }

        return $base;
    }

    protected function getType(): string
    {
        return $this->activeModule() === 'hr_d' ? 'doughnut' : 'bar';
    }

}
