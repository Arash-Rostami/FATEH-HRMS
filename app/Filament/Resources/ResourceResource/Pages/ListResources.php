<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Traits\FilamentHeaderActions;
use App\Filament\Pages\ListRecords;

class ListResources extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ResourceResource::class;
}