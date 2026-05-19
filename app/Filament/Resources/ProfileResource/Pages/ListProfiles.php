<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Profile;

class ListProfiles extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ProfileResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->extra['preferences']['show_list_tabs'] ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'working' => Tab::make('در حال کار')
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $this->getStats()->working_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('employment_type', 'working')),

            'probational' => Tab::make('آزمایشی')
                ->icon('heroicon-o-clock')
                ->badge(fn() => $this->getStats()->probational_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('employment_type', 'probational')),

            'terminated' => Tab::make('پایان همکاری')
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $this->getStats()->terminated_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('employment_type', 'terminated')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Profile::query()
            ->selectRaw("
                SUM(CASE WHEN employment_type = 'working' THEN 1 ELSE 0 END) AS working_count,
                SUM(CASE WHEN employment_type = 'probational' THEN 1 ELSE 0 END) AS probational_count,
                SUM(CASE WHEN employment_type = 'terminated' THEN 1 ELSE 0 END) AS terminated_count
            ")
            ->first());
    }
}