<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;

class EditLink extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;
    protected static string $resource = LinkResource::class;

    protected function trackedFileColumns(): array
    {
        return ['icon' => [], 'image' => []];
    }
}
