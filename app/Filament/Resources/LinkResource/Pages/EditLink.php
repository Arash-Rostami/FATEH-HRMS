<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditLink extends EditRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;
    protected static string $resource = LinkResource::class;
}
