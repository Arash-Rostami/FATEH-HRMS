<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateResource extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = ResourceResource::class;
}
