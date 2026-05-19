<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Permission;

class ListPermissions extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = PermissionResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'super_admins' => Tab::make('مدیران کل')
                ->icon('heroicon-o-star')
                ->badge(fn() => $this->getStats()->super_admin_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_super_admin', true)),

            'regular' => Tab::make('کاربران عادی')
                ->icon('heroicon-o-user')
                ->badge(fn() => $this->getStats()->regular_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_super_admin', false)),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Permission::query()
            ->selectRaw("
                SUM(CASE WHEN is_super_admin = 1 THEN 1 ELSE 0 END) AS super_admin_count,
                SUM(CASE WHEN is_super_admin = 0 THEN 1 ELSE 0 END) AS regular_count
            ")
            ->first());
    }
}