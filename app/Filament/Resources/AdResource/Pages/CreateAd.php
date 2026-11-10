<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;

class CreateAd extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = AdResource::class;
}
