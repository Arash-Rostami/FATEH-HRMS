<?php

namespace App\Filament\Resources\CorrespondenceResource\Pages;

use App\Filament\Pages\ViewRecord;
use App\Filament\Resources\CorrespondenceResource;
use App\Traits\Filament\FilamentHeaderActions;

class ViewCorrespondence extends ViewRecord
{
    use FilamentHeaderActions;

    protected static string $resource = CorrespondenceResource::class;
}
