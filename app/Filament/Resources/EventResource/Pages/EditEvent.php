<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;
    protected static string $resource = EventResource::class;
}
