<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Traits\{FilamentDateHandler, FilamentPageBehavior, PreparesAdminTaskCreate};
use App\Filament\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    use FilamentPageBehavior, FilamentDateHandler, PreparesAdminTaskCreate;

    protected static string $resource = TaskResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'deadline', 'default_time' => '12:00'],
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $detail = $this->form->getRawState()['detail'] ?? [];

        return $this->prepareTaskCreate(
            $this->mergeDeadline($data),
            [$detail['responsible_user_id'] ?? null, ...($detail['collaborators'] ?? [])]
        );
    }

    protected function afterCreate(): void
    {
        $this->ensureTaskDetail($this->record);
    }
}
