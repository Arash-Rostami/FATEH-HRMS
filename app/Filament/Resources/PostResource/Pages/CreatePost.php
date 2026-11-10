<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = PostResource::class;
}
