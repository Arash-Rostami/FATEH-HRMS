<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditPermission extends EditRecord
{
    use FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = PermissionResource::class;
}
