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

class HrEngagementChart extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = true;
    protected static ?int $sort = 4;
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
                    'hr_n' => __('resources/dashboard/strings.hr_analytics.engagement.modules.hr_n'),
                    'hr_o' => __('resources/dashboard/strings.hr_analytics.engagement.modules.hr_o'),
                    'hr_p' => __('resources/dashboard/strings.hr_analytics.engagement.modules.hr_p'),
                    'hr_q' => __('resources/dashboard/strings.hr_analytics.engagement.modules.hr_q'),
                ]),
        ]);
    }

    public function getDescription(): ?string
    {
        return match ($this->activeModule()) {
            'hr_n' => __('resources/dashboard/strings.hr_analytics.engagement.descriptions.hr_n'),
            'hr_o' => __('resources/dashboard/strings.hr_analytics.engagement.descriptions.hr_o'),
            'hr_p' => __('resources/dashboard/strings.hr_analytics.engagement.descriptions.hr_p'),
            'hr_q' => __('resources/dashboard/strings.hr_analytics.engagement.descriptions.hr_q'),
            default => __('resources/dashboard/strings.chart_widgets.default_description'),
        };
    }

    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(Blade::render(
            '<span class="relative -top-5 w-full !mb-0 !pb-0"><x-ui.title icon="analytics" :title="$title" count="4" countLabel="آیتم آماری" /></span>',
            ['title' => __('resources/dashboard/strings.hr_analytics.engagement.label')]
        ));
    }

    public function getHrNData(): array
    {
        return app(HrAnalyticsService::class)->getHrNData();
    }

    public function getHrOData(): array
    {
        return app(HrAnalyticsService::class)->getHrOData();
    }

    public function getHrPData(): array
    {
        return app(HrAnalyticsService::class)->getHrPData();
    }

    public function getHrQData(): array
    {
        return app(HrAnalyticsService::class)->getHrQData();
    }

    protected function activeModule(): ?string
    {
        return $this->filters['module'] ?? null;
    }

    protected function getData(): array
    {
        return match ($this->activeModule()) {
            'hr_n' => $this->getHrNData(),
            'hr_o' => $this->getHrOData(),
            'hr_p' => $this->getHrPData(),
            'hr_q' => $this->getHrQData(),
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
                'x' => ['ticks' => ['font' => ['family' => $fontFamily]], 'stacked' => true, 'beginAtZero' => true],
                'y' => ['ticks' => ['font' => ['family' => $fontFamily]], 'stacked' => true],
            ],
        ];

        $module = $this->activeModule();

        if (in_array($module, ['hr_o', 'hr_p', 'hr_q'], true)) {
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
