<?php

namespace App\Filament\Resources\ReminderResource\Pages;

use App\Filament\Resources\ReminderResource;
use App\Traits\{FilamentDateHandler, FilamentPageBehavior};
use App\Filament\Pages\CreateRecord;

class CreateReminder extends CreateRecord
{
    use FilamentPageBehavior, FilamentDateHandler;

    protected static string $resource = ReminderResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'due_at', 'default_time' => '09:00'],
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mergeDeadline($data);
    }
}
