<?php

namespace App\Filament\Resources\ThsResource\Pages;

use App\Filament\Resources\ThsResource;
use App\Traits\{FilamentHeaderActions, FilamentPageBehavior, FilamentDateHandler};
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    use FilamentHeaderActions, FilamentPageBehavior, FilamentDateHandler;

    protected static string $resource = ThsResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'completion_deadline', 'default_time' => '08:00'],
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mergeDeadline($data);
    }
}
