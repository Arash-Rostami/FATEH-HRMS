<?php

namespace App\Filament\Resources\AuthorityResource\Pages;

use App\Filament\Resources\AuthorityResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Authority;

class ListAuthorities extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = AuthorityResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->extra['preferences']['show_list_tabs'] ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'main_duty' => Tab::make('وظایف اصلی')
                ->icon('heroicon-o-briefcase')
                ->badge(fn() => $this->getStats()->main_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('sub_duty', false)),

            'sub_duty' => Tab::make('وظایف فرعی')
                ->icon('heroicon-o-clipboard-document-list')
                ->badge(fn() => $this->getStats()->sub_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('sub_duty', true)),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Authority::query()
            ->selectRaw("
                SUM(CASE WHEN sub_duty = 0 THEN 1 ELSE 0 END) AS main_count,
                SUM(CASE WHEN sub_duty = 1 THEN 1 ELSE 0 END) AS sub_count
            ")
            ->first());
    }
}