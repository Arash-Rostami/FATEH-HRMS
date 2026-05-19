<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Photo;

class ListGallery extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = GalleryResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'with_event_date' => Tab::make('دارای تاریخ رویداد')
                ->icon('heroicon-o-calendar')
                ->badge(fn() => $this->getStats()->with_date_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('event_date')),

            'no_event_date' => Tab::make('بدون تاریخ رویداد')
                ->icon('heroicon-o-calendar-days')
                ->badge(fn() => $this->getStats()->no_date_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('event_date')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Photo::query()
            ->selectRaw("
                SUM(CASE WHEN event_date IS NOT NULL THEN 1 ELSE 0 END) AS with_date_count,
                SUM(CASE WHEN event_date IS NULL THEN 1 ELSE 0 END) AS no_date_count
            ")
            ->first());
    }
}