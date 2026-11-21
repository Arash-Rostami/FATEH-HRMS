<?php

namespace App\Filament\Resources\KnowledgeProgressResource\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\KnowledgeProgressResource;
use App\Filament\Resources\ReportResource;
use App\Models\KnowledgeProgress;
use App\Traits\Filament\FilamentHeaderActions;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListKnowledgeProgress extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = KnowledgeProgressResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'completed' => Tab::make(__('resources/media/strings.progress.export.completed_yes'))
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $this->getStats()->completed_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('completed_at')),

            'in_progress' => Tab::make(__('resources/media/strings.progress.tabs.in_progress'))
                ->icon('heroicon-o-play-circle')
                ->badge(fn() => $this->getStats()->open_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('completed_at')),
        ];
    }

    protected function listHeaderActions(): array
    {
        return [...ReportResource::switchActions(static::$resource)];
    }

    private function getStats(): object
    {
        return once(fn() => KnowledgeProgress::query()
            ->selectRaw("
                SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_count,
                SUM(CASE WHEN completed_at IS NULL THEN 1 ELSE 0 END) AS open_count
            ")
            ->first());
    }
}
