<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateAd extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = AdResource::class;
}
