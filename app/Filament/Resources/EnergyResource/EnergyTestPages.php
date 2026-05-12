<?php

namespace App\Filament\Resources\EnergyTestResource\Pages;

use App\Filament\Resources\EnergyTestResource;
use App\Filament\Resources\EnergyTestResource\Widgets\EnergyTestStatsWidget;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\{ListRecords, ViewRecord};
use Illuminate\Database\Eloquent\Builder;

// ─── List ─────────────────────────────────────────────────────────────────────

class ListEnergyTests extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = EnergyTestResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('resources/energy_test/strings.tabs.all'))
                ->icon('heroicon-o-list-bullet'),

            'high' => Tab::make(__('resources/energy_test/strings.tabs.high'))
                ->icon('heroicon-o-arrow-trending-up')
                ->badge(fn() => \App\Models\EnergyTest::where('overall_score', '>=', 70)->count() ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('overall_score', '>=', 70)),

            'medium' => Tab::make(__('resources/energy_test/strings.tabs.medium'))
                ->icon('heroicon-o-minus')
                ->modifyQueryUsing(fn(Builder $q) => $q
                    ->where('overall_score', '>=', 45)
                    ->where('overall_score', '<', 70)
                ),

            'low' => Tab::make(__('resources/energy_test/strings.tabs.low'))
                ->icon('heroicon-o-arrow-trending-down')
                ->badge(fn() => \App\Models\EnergyTest::where('overall_score', '<', 45)->count() ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('overall_score', '<', 45)),

            'last_month' => Tab::make(__('resources/energy_test/strings.tabs.last_month'))
                ->icon('heroicon-o-calendar')
                ->badge(fn() => \App\Models\EnergyTest::where('completed_at', '>=', now()->subDays(30))->count() ?: null)
                ->badgeColor('primary')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('completed_at', '>=', now()->subDays(30))),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [EnergyTestStatsWidget::class];
    }
}

// ─── View ─────────────────────────────────────────────────────────────────────

class ViewEnergyTest extends ViewRecord
{
    use FilamentHeaderActions;

    protected static string $resource = EnergyTestResource::class;
}
