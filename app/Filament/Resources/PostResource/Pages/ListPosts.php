<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Post;

class ListPosts extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = PostResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->extra['preferences']['show_list_tabs'] ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'pinned' => Tab::make('سنجاق شده')
                ->icon('heroicon-o-paper-clip')
                ->badge(fn() => $this->getStats()->pinned_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('pinned', true)),

            'unpinned' => Tab::make('عادی')
                ->icon('heroicon-o-document-text')
                ->badge(fn() => $this->getStats()->unpinned_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('pinned', false)),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Post::query()
            ->selectRaw("
                SUM(CASE WHEN pinned = 1 THEN 1 ELSE 0 END) AS pinned_count,
                SUM(CASE WHEN pinned = 0 THEN 1 ELSE 0 END) AS unpinned_count
            ")
            ->first());
    }
}