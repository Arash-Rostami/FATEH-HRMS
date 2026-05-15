<?php

namespace App\Filament\Resources\SuggestionResource\Pages;

use App\Filament\Resources\SuggestionResource;
use App\Models\Suggestion;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSuggestions extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = SuggestionResource::class;

    public function getTabs(): array
    {
        $stats = $this->getStats();

        return [
            'all' => Tab::make(__('resources/suggestion/strings.tabs.all'))
                ->icon('heroicon-o-list-bullet'),

            'pending' => Tab::make(__('resources/suggestion/strings.tabs.pending'))
                ->icon('heroicon-o-clock')
                ->badge(fn() => $stats->pending_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', 'pending')),

            'team_remarks' => Tab::make(__('resources/suggestion/strings.tabs.team_remarks'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->badge(fn() => $stats->team_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', 'team_remarks')),

            'dept_remarks' => Tab::make(__('resources/suggestion/strings.tabs.dept_remarks'))
                ->icon('heroicon-o-building-office-2')
                ->badge(fn() => $stats->dept_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', 'dept_remarks')),

            'awaiting_decision' => Tab::make(__('resources/suggestion/strings.tabs.awaiting_decision'))
                ->icon('heroicon-o-scale')
                ->badge(fn() => $stats->awaiting_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', 'awaiting_decision')),

            'accepted' => Tab::make(__('resources/suggestion/strings.tabs.accepted'))
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $stats->accepted_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', 'accepted')),

            'rejected' => Tab::make(__('resources/suggestion/strings.tabs.rejected'))
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $stats->rejected_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', 'rejected')),

            'under_review' => Tab::make(__('resources/suggestion/strings.tabs.under_review'))
                ->icon('heroicon-o-magnifying-glass')
                ->badge(fn() => $stats->under_review_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', 'under_review')),

            'closed' => Tab::make(__('resources/suggestion/strings.tabs.closed'))
                ->icon('heroicon-o-lock-closed')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', 'closed')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Suggestion::query()
            ->selectRaw("
                SUM(CASE WHEN stage = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN stage = 'team_remarks' THEN 1 ELSE 0 END) AS team_count,
                SUM(CASE WHEN stage = 'dept_remarks' THEN 1 ELSE 0 END) AS dept_count,
                SUM(CASE WHEN stage = 'awaiting_decision' THEN 1 ELSE 0 END) AS awaiting_count,
                SUM(CASE WHEN stage = 'accepted' THEN 1 ELSE 0 END) AS accepted_count,
                SUM(CASE WHEN stage = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                SUM(CASE WHEN stage = 'under_review' THEN 1 ELSE 0 END) AS under_review_count
            ")
            ->first());
    }
}
