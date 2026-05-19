<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Ad;

class ListAds extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = AdResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->extra['preferences']['show_list_tabs'] ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'active' => Tab::make('فعال')
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $this->getStats()->active_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('active', true)),

            'inactive' => Tab::make('غیرفعال')
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $this->getStats()->inactive_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('active', false)),

            'male' => Tab::make('آقایان')
                ->icon('heroicon-o-user')
                ->badge(fn() => $this->getStats()->male_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('gender', 'Male')),

            'female' => Tab::make('خانم‌ها')
                ->icon('heroicon-o-user')
                ->badge(fn() => $this->getStats()->female_count ?: null)
                ->badgeColor('pink')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('gender', 'Female')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Ad::query()
            ->selectRaw("
                SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) AS inactive_count,
                SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) AS male_count,
                SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) AS female_count
            ")
            ->first());
    }
}