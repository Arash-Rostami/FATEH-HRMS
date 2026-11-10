<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;

class EditContact extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = ContactResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['is_edited'] = true;
        return $data;
    }
}
