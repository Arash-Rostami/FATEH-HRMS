<?php

namespace App\Filament\Resources\FeedResource\Pages;

use App\Filament\Resources\FeedResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Feed;

class ListFeeds extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = FeedResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->extra['preferences']['show_list_tabs'] ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'today' => Tab::make('امروز')
                ->icon('heroicon-o-calendar-days')
                ->badge(fn() => $this->getStats()->today_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('created_at', today())),

            'with_media' => Tab::make('دارای مدیا')
                ->icon('heroicon-o-photo')
                ->badge(fn() => $this->getStats()->with_media_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('media_paths')),

            'poll' => Tab::make('نظرسنجی')
                ->icon('heroicon-o-chart-bar')
                ->badge(fn() => $this->getStats()->poll_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('poll_options')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Feed::query()
            ->selectRaw("
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_count,
                SUM(CASE WHEN media_paths IS NOT NULL THEN 1 ELSE 0 END) AS with_media_count,
                SUM(CASE WHEN poll_options IS NOT NULL THEN 1 ELSE 0 END) AS poll_count
            ")
            ->first());
    }
}