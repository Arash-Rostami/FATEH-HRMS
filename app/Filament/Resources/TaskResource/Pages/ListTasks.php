<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = TaskResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('resources/task/strings.tabs.all'))
                ->icon('heroicon-o-list-bullet'),

            'todo' => Tab::make(__('resources/task/strings.tabs.todo'))
                ->icon('heroicon-o-clock')
                ->badge(fn() => $this->getStats()->todo_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('status', 'todo')
                ),

            'in_progress' => Tab::make(__('resources/task/strings.tabs.in_progress'))
                ->icon('heroicon-o-arrow-path')
                ->badge(fn() => $this->getStats()->in_progress_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('status', 'in-progress')
                ),

            'done' => Tab::make(__('resources/task/strings.tabs.done'))
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('status', 'done')
                ),

            'overdue' => Tab::make(__('resources/task/strings.tabs.overdue'))
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(fn() => $this->getStats()->overdue_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->whereNotNull('deadline')
                        ->where('deadline', '<', now())
                        ->where('status', '!=', 'done')
                ),

            'delegated' => Tab::make(__('resources/task/strings.tabs.delegated'))
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->whereNotNull('assigned_to')
                        ->whereColumn('assigned_to', '!=', 'user_id')
                ),

            'trashed' => Tab::make(__('resources/task/strings.tabs.trashed'))
                ->icon('heroicon-o-trash')
                ->badge(fn() => $this->getStats()->trashed_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->onlyTrashed()
                ),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Task::query()
            ->withTrashed()
            ->selectRaw("
            SUM(CASE WHEN status = 'todo' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS todo_count,
            SUM(CASE WHEN status = 'in-progress' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS in_progress_count,
            SUM(CASE WHEN deadline IS NOT NULL AND deadline < ? AND status != 'done' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS overdue_count,
            SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS trashed_count
        ", [now()])
            ->first()
        );
    }
}
