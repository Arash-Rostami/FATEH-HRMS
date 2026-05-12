<?php

namespace App\Filament\Resources\DmsResource\Pages;

use App\Filament\Resources\DmsResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditDms extends EditRecord
{
    use FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = DmsResource::class;
}

