<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Filament\Resources\ResourceResource\Schemas\ResourceFormPresenter;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateResource extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = ResourceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ResourceFormPresenter::normalizeMetadata($data);
    }
}
