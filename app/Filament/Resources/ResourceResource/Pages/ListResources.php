<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListResources extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ResourceResource::class;
}