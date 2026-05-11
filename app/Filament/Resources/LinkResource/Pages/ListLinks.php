<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListLinks extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = LinkResource::class;
}
