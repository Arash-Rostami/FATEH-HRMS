<?php

namespace App\Filament\Resources\ThsResource\Pages;

use App\Filament\Resources\ThsResource;
use App\Traits\{FilamentDateHandler, FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior};
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior, FilamentDateHandler;

    protected static string $resource = ThsResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'completion_deadline', 'default_time' => '08:00'],
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mergeDeadline($data);
    }
}
