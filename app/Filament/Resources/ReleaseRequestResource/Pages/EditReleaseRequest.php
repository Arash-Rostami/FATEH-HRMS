<?php

namespace App\Filament\Resources\ReleaseRequestResource\Pages;

use App\Filament\Resources\ReleaseRequestResource;
use App\Traits\FilamentEditHeading;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditReleaseRequest extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = ReleaseRequestResource::class;
}