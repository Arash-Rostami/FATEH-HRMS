<?php

namespace App\Filament\Resources\DmsResource\Pages;

use App\Filament\Resources\DmsResource;
use App\Models\DMS;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDms extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = DmsResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make(__('resources/dms/strings.tabs.all'))
                ->icon('heroicon-o-list-bullet'),

            'live' => Tab::make(__('resources/dms/strings.tabs.live'))
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $this->getStats()->live_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'live')),

            'under_review' => Tab::make(__('resources/dms/strings.tabs.under_review'))
                ->icon('heroicon-o-clock')
                ->badge(fn() => $this->getStats()->under_review_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'under_review')),

            'obsolete' => Tab::make(__('resources/dms/strings.tabs.obsolete'))
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $this->getStats()->archived_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'obsolete')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => DMS::getDocumentCounts());
    }
}
