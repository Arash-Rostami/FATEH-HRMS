<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = UserResource::class;

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
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'active')),

            'inactive' => Tab::make('غیرفعال')
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $this->getStats()->inactive_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'inactive')),

            'admins' => Tab::make('مدیران')
                ->icon('heroicon-o-shield-check')
                ->badge(fn() => $this->getStats()->admin_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('role', ['admin', 'developer'])),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => User::query()
            ->selectRaw("
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_count,
                SUM(CASE WHEN role IN ('admin', 'developer') THEN 1 ELSE 0 END) AS admin_count
            ")
            ->first());
    }
}
