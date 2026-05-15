<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = PermissionResource::class;
}
