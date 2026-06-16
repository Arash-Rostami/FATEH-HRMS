<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = UserResource::class;

}
