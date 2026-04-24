<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = UserResource::class;
}
