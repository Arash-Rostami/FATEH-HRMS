<?php

namespace App\Filament\Widgets;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public string $activeDashboardTab = 'overview';

    public function getHeading(): string|Htmlable
    {
        return view('filament.widgets.dashboard');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            ...(method_exists($this, 'getFiltersForm') ? [$this->getFiltersFormContentComponent()] : []),
            Tabs::make()
                ->livewireProperty('activeDashboardTab')
                ->tabs([
                    'overview' => Tab::make(__('resources/dashboard/strings.tabs.overview'))
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([$this->widgetsGrid($this->splitWidgets(isChart: false))]),
                    'charts' => Tab::make('تحلیل‌های نموداری')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([$this->widgetsGrid($this->splitWidgets(isChart: true))]),
                ]),
        ]);
    }

    protected function widgetsGrid(array $widgets): Grid
    {
        return Grid::make($this->getColumns())
            ->schema(fn (): array => $this->getWidgetsSchemaComponents($widgets));
    }

    /**
     * @return array<class-string<\Filament\Widgets\Widget> | WidgetConfiguration>
     */
    protected function splitWidgets(bool $isChart): array
    {
        return array_values(array_filter(
            $this->getWidgets(),
            fn (string|WidgetConfiguration $widget): bool => is_subclass_of(
                $this->normalizeWidgetClass($widget),
                ChartWidget::class
            ) === $isChart,
        ));
    }
}
