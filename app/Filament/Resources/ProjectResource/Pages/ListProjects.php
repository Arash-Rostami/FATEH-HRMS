<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Traits\FilamentHeaderActions;
use App\Filament\Pages\ListRecords;

class ListProjects extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ProjectResource::class;
}
