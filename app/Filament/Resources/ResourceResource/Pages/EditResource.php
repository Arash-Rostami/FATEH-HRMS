<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Filament\Resources\ResourceResource\Schemas\ResourceFormPresenter;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;

class EditResource extends EditRecord
{
    use FilamentEditHeading, FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = ResourceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ResourceFormPresenter::normalizeMetadata($data);
    }

    protected function trackedFileColumns(): array
    {
        return ['image' => []];
    }
}
