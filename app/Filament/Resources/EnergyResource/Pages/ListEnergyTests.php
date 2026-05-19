<?php

namespace App\Filament\Resources\EnergyResource\Pages;

use App\Filament\Resources\EnergyTestResource;
use App\Models\EnergyTest;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEnergyTests extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = EnergyTestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        if (!(auth()->user()?->extra['preferences']['show_list_tabs'] ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make(__('resources/energy/strings.tabs.all'))
                ->icon('heroicon-o-list-bullet'),

            'high' => Tab::make('😔 ' . __('resources/energy/strings.tabs.high'))
                ->badge(fn() => $this->getStats()->high_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('overall_score', '>=', 12)
                ),

            'medium' => Tab::make('😐 ' . __('resources/energy/strings.tabs.medium'))
                ->badge(fn() => $this->getStats()->medium_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->where('overall_score', '>', 8)
                        ->where('overall_score', '<', 12)
                ),

            'low' => Tab::make('😊 ' . __('resources/energy/strings.tabs.low'))
                ->badge(fn() => $this->getStats()->low_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('overall_score', '<=', 8)
                ),

            'last_month' => Tab::make(__('resources/energy/strings.tabs.last_month'))
                ->icon('heroicon-o-calendar')
                ->badge(fn() => $this->getStats()->last_month_count ?: null)
                ->badgeColor('primary')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('completed_at', '>=', now()->subDays(30))
                ),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => EnergyTest::query()
            ->selectRaw("
                SUM(CASE WHEN overall_score >= 12 THEN 1 ELSE 0 END) AS high_count,
                SUM(CASE WHEN overall_score > 8 AND overall_score < 12 THEN 1 ELSE 0 END) AS medium_count,
                SUM(CASE WHEN overall_score <= 8 THEN 1 ELSE 0 END) AS low_count,
                SUM(CASE WHEN completed_at >= ? THEN 1 ELSE 0 END) AS last_month_count
            ", [now()->subDays(30)])
            ->first()
        );
    }
}
