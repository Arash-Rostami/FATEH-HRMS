<?php

namespace App\Filament\Resources\ChannelResource\Pages;

use App\Filament\Resources\ChannelResource;
use App\Traits\FilamentHeaderActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChannels extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->label(__('resources/general/strings.table.action_create')),
        ];
    }
}