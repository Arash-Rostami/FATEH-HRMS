<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;

class CreateLink extends CreateRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = LinkResource::class;
}
