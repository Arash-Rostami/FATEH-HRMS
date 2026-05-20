<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Models\Message;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContacts extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }


        $pruningThreshold = now()->subDays(30);

        return [
            'all' => Tab::make(__('resources/contact/strings.tabs.all'))
                ->icon('heroicon-o-chat-bubble-left-ellipsis'),

            'unread' => Tab::make(__('resources/contact/strings.tabs.unread'))
                ->icon('heroicon-o-envelope')
                ->badge(fn() => $this->getStats()->unread_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->whereNull('read_at')
                ),

            'edited' => Tab::make(__('resources/contact/strings.tabs.edited'))
                ->icon('heroicon-o-pencil-square')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('is_edited', true)
                ),

            'trashed' => Tab::make(__('resources/contact/strings.tabs.trashed'))
                ->icon('heroicon-o-trash')
                ->badge(fn() => $this->getStats()->trashed_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->onlyTrashed()
                ),

            'pruning_soon' => Tab::make(__('resources/contact/strings.tabs.pruning_soon'))
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(fn() => $this->getStats()->pruning_soon_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->onlyTrashed()
                        ->where('deleted_at', '<=', $pruningThreshold)
                ),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Message::query()
            ->withTrashed()
            ->selectRaw("
                SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) AS unread_count,
                SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS trashed_count,
                SUM(CASE WHEN deleted_at IS NOT NULL AND deleted_at <= ? THEN 1 ELSE 0 END) AS pruning_soon_count
            ", [now()->subDays(30)])
            ->first()
        );
    }
}
