<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListProfiles extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ProfileResource::class;
}
