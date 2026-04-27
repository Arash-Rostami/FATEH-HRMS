<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditAd extends EditRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = AdResource::class;
}
