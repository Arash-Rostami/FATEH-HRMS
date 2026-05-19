<?php

namespace App\Filament\Resources\FAQResource\Pages;

use App\Filament\Resources\FAQResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\FAQ;

class ListFAQs extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = FAQResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->extra['preferences']['show_list_tabs'] ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'has_department' => Tab::make('مربوط به دپارتمان')
                ->icon('heroicon-o-building-office-2')
                ->badge(fn() => $this->getStats()->has_dept_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('department_id')),

            'general' => Tab::make('عمومی')
                ->icon('heroicon-o-globe-alt')
                ->badge(fn() => $this->getStats()->general_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('department_id')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => FAQ::query()
            ->selectRaw("
                SUM(CASE WHEN department_id IS NOT NULL THEN 1 ELSE 0 END) AS has_dept_count,
                SUM(CASE WHEN department_id IS NULL THEN 1 ELSE 0 END) AS general_count
            ")
            ->first());
    }
}