<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Filament\Resources\ResourceResource\Schemas\ResourceFormPresenter;
use App\Traits\FilamentEditHeading;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditResource extends EditRecord
{
    use FilamentEditHeading, FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = ResourceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ResourceFormPresenter::normalizeMetadata($data);
    }
}
