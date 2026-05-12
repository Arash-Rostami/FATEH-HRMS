<?php

namespace App\Filament\Resources\DmsResource\Pages;

use App\Filament\Resources\DmsResource;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateDms extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = DmsResource::class;
}
