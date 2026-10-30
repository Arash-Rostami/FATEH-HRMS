<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Traits\FilamentHeaderActions;
use App\Filament\Pages\ListRecords;

class ListReservations extends ListRecords
{
    use FilamentHeaderActions {
        getHeaderActions as private baseHeaderActions;
    }

    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [...ReservationResource::switchActions(static::$resource), ...$this->baseHeaderActions()];
    }
}
