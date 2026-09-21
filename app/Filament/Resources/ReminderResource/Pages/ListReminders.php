<?php

namespace App\Filament\Resources\ReminderResource\Pages;

use App\Filament\Resources\ReminderResource;
use App\Traits\FilamentHeaderActions;
use App\Filament\Pages\ListRecords;

class ListReminders extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ReminderResource::class;
}
