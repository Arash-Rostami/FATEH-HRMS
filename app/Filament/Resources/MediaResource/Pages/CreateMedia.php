<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\MediaResource;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;

class CreateMedia extends CreateRecord
{
    use FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = MediaResource::class;
}
