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

class HrQualificationChart extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = true;
    protected static ?int $sort = 2;
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
                    'hr_e' => __('resources/dashboard/strings.hr_analytics.qualification.modules.hr_e'),
                    'hr_f' => __('resources/dashboard/strings.hr_analytics.qualification.modules.hr_f'),
                    'hr_g' => __('resources/dashboard/strings.hr_analytics.qualification.modules.hr_g'),
                    'hr_h' => __('resources/dashboard/strings.hr_analytics.qualification.modules.hr_h'),
                ]),
        ]);
    }

    public function getDescription(): ?string
    {
        return match ($this->activeModule()) {
            'hr_e' => __('resources/dashboard/strings.hr_analytics.qualification.descriptions.hr_e'),
            'hr_f' => __('resources/dashboard/strings.hr_analytics.qualification.descriptions.hr_f'),
            'hr_g' => __('resources/dashboard/strings.hr_analytics.qualification.descriptions.hr_g'),
            'hr_h' => __('resources/dashboard/strings.hr_analytics.qualification.descriptions.hr_h'),
            default => __('resources/dashboard/strings.chart_widgets.default_description'),
        };
    }

    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(Blade::render(
            '<span class="relative -top-5 w-full !mb-0 !pb-0"><x-ui.title icon="analytics" :title="$title" count="4" countLabel="آیتم آماری" /></span>',
            ['title' => __('resources/dashboard/strings.hr_analytics.qualification.label')]
        ));
    }

    public function getHrEData(): array
    {
        return app(HrAnalyticsService::class)->getHrEData();
    }

    public function getHrFData(): array
    {
        return app(HrAnalyticsService::class)->getHrFData();
    }

    public function getHrGData(): array
    {
        return app(HrAnalyticsService::class)->getHrGData();
    }

    public function getHrHData(): array
    {
        return app(HrAnalyticsService::class)->getHrHData();
    }

    protected function activeModule(): ?string
    {
        return $this->filters['module'] ?? null;
    }

    protected function getData(): array
    {
        return match ($this->activeModule()) {
            'hr_e' => $this->getHrEData(),
            'hr_f' => $this->getHrFData(),
            'hr_g' => $this->getHrGData(),
            'hr_h' => $this->getHrHData(),
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

        if ($this->activeModule() === 'hr_f') {
            $base['scales']['x']['stacked'] = false;
            $base['scales']['y']['stacked'] = false;
        }

        return $base;
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
