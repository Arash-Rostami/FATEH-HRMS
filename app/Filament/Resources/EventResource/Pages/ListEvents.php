<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    use FilamentHeaderActions;
    protected static string $resource = EventResource::class;
}
