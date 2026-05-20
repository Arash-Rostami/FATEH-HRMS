<?php

namespace App\Filament\Resources\OnboardingResource\Pages;

use App\Filament\Resources\OnboardingResource;
use App\Models\Onboarding;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOnboardings extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = OnboardingResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'active' => Tab::make('فعال')
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $this->getStats()->active_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_active', true)),

            'inactive' => Tab::make('غیرفعال')
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $this->getStats()->inactive_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_active', false)),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Onboarding::query()
            ->selectRaw("
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count
            ")
            ->first());
    }
}
