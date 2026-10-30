<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\ResourceResource;
use App\Traits\FilamentHeaderActions;
use App\Filament\Pages\ListRecords;

class ListResources extends ListRecords
{
    use FilamentHeaderActions {
        getHeaderActions as private baseHeaderActions;
    }

    protected static string $resource = ResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [...ReservationResource::switchActions(static::$resource), ...$this->baseHeaderActions()];
    }
}