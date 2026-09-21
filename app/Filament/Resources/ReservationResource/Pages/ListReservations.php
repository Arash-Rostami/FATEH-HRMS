<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Traits\FilamentHeaderActions;
use App\Filament\Pages\ListRecords;

class ListReservations extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ReservationResource::class;
}
