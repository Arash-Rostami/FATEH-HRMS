<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Link;

class ListLinks extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = LinkResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'internal' => Tab::make('داخلی')
                ->icon('heroicon-o-arrow-down-on-square')
                ->badge(fn() => $this->getStats()->internal_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('link', 'internal')),

            'external' => Tab::make('خارجی')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->badge(fn() => $this->getStats()->external_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('link', 'external')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Link::query()
            ->selectRaw("
                SUM(CASE WHEN link = 'internal' THEN 1 ELSE 0 END) AS internal_count,
                SUM(CASE WHEN link = 'external' THEN 1 ELSE 0 END) AS external_count
            ")
            ->first());
    }
}