<?php

namespace App\Filament\Resources\ReminderResource\Pages;

use App\Filament\Resources\ReminderResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListReminders extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ReminderResource::class;
}
