<?php

namespace App\Filament\Resources\DmsResource\Pages;

use App\Filament\Resources\DmsResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;

class EditDms extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = DmsResource::class;

    protected function trackedFileColumns(): array
    {
        return ['file' => [], 'extra_files' => []];
    }
}
