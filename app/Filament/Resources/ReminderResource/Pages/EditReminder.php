<?php

namespace App\Filament\Resources\ReminderResource\Pages;

use App\Filament\Resources\ReminderResource;
use App\Traits\{FilamentDateHandler, FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior};
use App\Filament\Pages\EditRecord;

class EditReminder extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior, FilamentDateHandler;

    protected static string $resource = ReminderResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'due_at', 'default_time' => '09:00'],
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mergeDeadline($data);
    }
}
