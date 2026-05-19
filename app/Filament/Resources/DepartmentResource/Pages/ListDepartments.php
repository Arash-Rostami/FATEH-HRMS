<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Department;

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

            'with_description' => Tab::make('دارای توضیحات')
                ->icon('heroicon-o-document-text')
                ->badge(fn() => $this->getStats()->with_description_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('description')),

            'no_description' => Tab::make('بدون توضیحات')
                ->icon('heroicon-o-document-minus')
                ->badge(fn() => $this->getStats()->no_description_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('description')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Department::query()
            ->selectRaw("
                SUM(CASE WHEN description IS NOT NULL THEN 1 ELSE 0 END) AS with_description_count,
                SUM(CASE WHEN description IS NULL THEN 1 ELSE 0 END) AS no_description_count
            ")
            ->first());
    }
}