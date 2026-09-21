<?php

namespace App\Filament\Resources\ChannelResource\Pages;

use App\Filament\Resources\ChannelResource;
use App\Traits\FilamentHeaderActions;
use App\Filament\Pages\ListRecords;

class ListChannels extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ChannelResource::class;
}