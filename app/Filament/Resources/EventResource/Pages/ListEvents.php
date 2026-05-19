<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Event;

class ListEvents extends ListRecords
{
    use FilamentHeaderActions;
    protected static string $resource = EventResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->extra['preferences']['show_list_tabs'] ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'upcoming' => Tab::make('پیش رو')
                ->icon('heroicon-o-clock')
                ->badge(fn() => $this->getStats()->upcoming_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('date', '>=', now())),

            'past' => Tab::make('گذشته')
                ->icon('heroicon-o-arrow-uturn-left')
                ->badge(fn() => $this->getStats()->past_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('date', '<', now())),

            'public' => Tab::make('عمومی')
                ->icon('heroicon-o-globe-alt')
                ->badge(fn() => $this->getStats()->public_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('private', false)),

            'private' => Tab::make('خصوصی')
                ->icon('heroicon-o-lock-closed')
                ->badge(fn() => $this->getStats()->private_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('private', true)),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Event::query()
            ->selectRaw("
                SUM(CASE WHEN date >= NOW() THEN 1 ELSE 0 END) AS upcoming_count,
                SUM(CASE WHEN date < NOW() THEN 1 ELSE 0 END) AS past_count,
                SUM(CASE WHEN private = 0 THEN 1 ELSE 0 END) AS public_count,
                SUM(CASE WHEN private = 1 THEN 1 ELSE 0 END) AS private_count
            ")
            ->first());
    }
}