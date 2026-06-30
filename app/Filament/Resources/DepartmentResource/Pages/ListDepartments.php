<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Models\Department;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDepartments extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = DepartmentResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'with_ticket_options' => Tab::make('دارای گزینه تیکت')
                ->icon('heroicon-o-adjustments-horizontal')
                ->badge(fn() => $this->getStats()->with_ticket_options_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('ticket_options')->whereJsonLength('ticket_options', '>', 0)),

            'no_ticket_options' => Tab::make('بدون گزینه تیکت')
                ->icon('heroicon-o-document-minus')
                ->badge(fn() => $this->getStats()->no_ticket_options_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->where(fn(Builder $q) => $q->whereNull('ticket_options')->orWhereJsonLength('ticket_options', 0))),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Department::query()
            ->selectRaw("
                SUM(CASE WHEN ticket_options IS NOT NULL AND JSON_LENGTH(ticket_options) > 0 THEN 1 ELSE 0 END) AS with_ticket_options_count,
                SUM(CASE WHEN ticket_options IS NULL OR JSON_LENGTH(ticket_options) = 0 THEN 1 ELSE 0 END) AS no_ticket_options_count
            ")
            ->first());
    }
}