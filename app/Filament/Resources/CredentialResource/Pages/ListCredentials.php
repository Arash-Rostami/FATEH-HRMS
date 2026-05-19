<?php

namespace App\Filament\Resources\CredentialResource\Pages;

use App\Filament\Resources\CredentialResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Credential;

class ListCredentials extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = CredentialResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'with_link' => Tab::make('دارای لینک')
                ->icon('heroicon-o-link')
                ->badge(fn() => $this->getStats()->with_link_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('link')),

            'no_link' => Tab::make('بدون لینک')
                ->icon('heroicon-o-link-slash')
                ->badge(fn() => $this->getStats()->no_link_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('link')),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Credential::query()
            ->selectRaw("
                SUM(CASE WHEN link IS NOT NULL THEN 1 ELSE 0 END) AS with_link_count,
                SUM(CASE WHEN link IS NULL THEN 1 ELSE 0 END) AS no_link_count
            ")
            ->first());
    }
}