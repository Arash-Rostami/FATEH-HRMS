<?php

namespace App\Filament\Resources\ThsResource\Pages;

use App\Filament\Resources\ThsResource;
use App\Livewire\Dashboard\Ths\Actions\AssignTicketAction;
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

    protected function afterCreate(): void
    {
        if ($this->record->assigned_to === null) {
            return;
        }

        app(AssignTicketAction::class)->syncForAdmin($this->record, $this->record->assigned_to);
    }
}
