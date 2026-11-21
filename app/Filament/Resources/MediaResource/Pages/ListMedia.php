<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\MediaResource;
use App\Filament\Resources\ReportResource;
use App\Models\Media;
use App\Traits\Filament\FilamentHeaderActions;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMedia extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = MediaResource::class;

    protected function listHeaderActions(): array
    {
        return [...ReportResource::switchActions(static::$resource), MediaResource::setupGuideAction()];
    }

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'series' => Tab::make(__('resources/media/strings.fields.kind_series'))
                ->icon('heroicon-o-rectangle-stack')
                ->badge(fn() => $this->getStats()->series_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('parent_id')->where('kind', 'series')),

            'singles' => Tab::make(__('resources/media/strings.tabs.singles'))
                ->icon('heroicon-o-play-circle')
                ->badge(fn() => $this->getStats()->singles_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('parent_id')->where('kind', '!=', 'series')),

            'episodes' => Tab::make(__('resources/media/strings.tabs.episodes'))
                ->icon('heroicon-o-list-bullet')
                ->badge(fn() => $this->getStats()->episodes_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('parent_id')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Media::query()
            ->selectRaw("
                SUM(CASE WHEN parent_id IS NULL AND kind = 'series' THEN 1 ELSE 0 END) AS series_count,
                SUM(CASE WHEN parent_id IS NULL AND kind != 'series' THEN 1 ELSE 0 END) AS singles_count,
                SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) AS episodes_count
            ")
            ->first());
    }
}
