<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Models\Profile;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProfiles extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ProfileResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'working' => Tab::make('در حال کار')
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $this->getStats()->working_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('employment_status', 'working')),

            'probational' => Tab::make('آزمایشی')
                ->icon('heroicon-o-clock')
                ->badge(fn() => $this->getStats()->probational_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('employment_status', 'probational')),

            'terminated' => Tab::make('پایان همکاری')
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $this->getStats()->terminated_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('employment_status', 'terminated')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Profile::query()
            ->selectRaw("
                SUM(CASE WHEN employment_status = 'working' THEN 1 ELSE 0 END) AS working_count,
                SUM(CASE WHEN employment_status = 'probational' THEN 1 ELSE 0 END) AS probational_count,
                SUM(CASE WHEN employment_status = 'terminated' THEN 1 ELSE 0 END) AS terminated_count
            ")
            ->first());
    }
}
