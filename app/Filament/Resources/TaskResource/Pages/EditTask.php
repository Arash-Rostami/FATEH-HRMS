<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Traits\{FilamentDateHandler, FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior};
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior, FilamentDateHandler;

    protected static string $resource = TaskResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'deadline', 'default_time' => '00:00'],
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mergeDeadline($data);
    }
}
