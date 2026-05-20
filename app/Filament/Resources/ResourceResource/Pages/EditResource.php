<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditResource extends EditRecord
{
    use FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = ResourceResource::class;
}
