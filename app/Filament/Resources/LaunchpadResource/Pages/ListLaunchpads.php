<?php

namespace App\Filament\Resources\LaunchpadResource\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\LaunchpadResource;
use App\Traits\Filament\FilamentHeaderActions;

class ListLaunchpads extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = LaunchpadResource::class;

    protected function listHeaderActions(): array
    {
        return [LaunchpadResource::setupGuideAction()];
    }
}
