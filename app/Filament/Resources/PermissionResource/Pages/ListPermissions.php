<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = PermissionResource::class;
}
