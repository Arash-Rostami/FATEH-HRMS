<?php

namespace App\Filament\Resources\ReleaseRequestResource\Pages;

use App\Filament\Resources\ReleaseRequestResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;

class EditReleaseRequest extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = ReleaseRequestResource::class;

    protected function trackedFileColumns(): array
    {
        return ['attachments' => ['path']];
    }
}
