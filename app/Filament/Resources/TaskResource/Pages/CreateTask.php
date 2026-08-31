<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Traits\{FilamentDateHandler, FilamentPageBehavior};
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    use FilamentPageBehavior, FilamentDateHandler;

    protected static string $resource = TaskResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'deadline', 'default_time' => '12:00'],
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mergeDeadline($data);
    }
}
