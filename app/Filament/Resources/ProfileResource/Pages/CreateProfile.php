<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Traits\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;

class CreateProfile extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = ProfileResource::class;

}
