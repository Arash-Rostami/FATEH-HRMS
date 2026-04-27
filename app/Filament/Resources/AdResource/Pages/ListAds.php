<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListAds extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = AdResource::class;
}
