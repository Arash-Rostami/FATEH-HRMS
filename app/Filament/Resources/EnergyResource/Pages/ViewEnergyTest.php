<?php

namespace App\Filament\Resources\EnergyResource\Pages;

use App\Filament\Resources\EnergyTestResource;
use App\Traits\FilamentHeaderActions;
use App\Filament\Pages\ViewRecord;

class ViewEnergyTest extends ViewRecord
{
    use FilamentHeaderActions;

    protected static string $resource = EnergyTestResource::class;
}
