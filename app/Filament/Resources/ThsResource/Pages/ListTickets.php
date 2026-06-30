<?php

namespace App\Filament\Resources\ThsResource\Pages;

use App\Filament\Resources\ThsResource;
use App\Models\Ticket;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTickets extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ThsResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make(__('resources/ths/strings.tabs.all'))
                ->icon('heroicon-o-list-bullet'),

            'open' => Tab::make(__('resources/ths/strings.tabs.open'))
                ->icon('heroicon-o-envelope-open')
                ->badge(fn() => $this->getStats()->open_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('status', 'open')
                ),

            'in_progress' => Tab::make(__('resources/ths/strings.tabs.in_progress'))
                ->icon('heroicon-o-arrow-path')
                ->badge(fn() => $this->getStats()->in_progress_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('status', 'in-progress')
                ),
            'closed' => Tab::make(__('resources/ths/strings.tabs.closed'))
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('status', 'closed')
                ),

            'unassigned' => Tab::make(__('resources/ths/strings.tabs.unassigned'))
                ->icon('heroicon-o-user-minus')
                ->badge(fn() => $this->getStats()->unassigned_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->whereNull('assigned_to')->whereIn('status', ['open', 'in-progress'])
                ),

            'overdue' => Tab::make(__('resources/ths/strings.tabs.overdue'))
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(fn() => $this->getStats()->overdue_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->whereNotNull('completion_deadline')
                        ->where('completion_deadline', '<', now())
                        ->where('status', '!=', 'closed')
                ),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Ticket::query()
            ->selectRaw("
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) AS in_progress_count,
                SUM(CASE WHEN assigned_to IS NULL AND status IN ('open', 'in-progress') THEN 1 ELSE 0 END) AS unassigned_count,
                SUM(CASE WHEN completion_deadline IS NOT NULL AND completion_deadline < ? AND status != 'closed' THEN 1 ELSE 0 END) AS overdue_count
            ", [now()])
            ->first()
        );
    }
}
