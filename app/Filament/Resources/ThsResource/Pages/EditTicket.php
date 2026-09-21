<?php

namespace App\Filament\Resources\ThsResource\Pages;

use App\Filament\Resources\ThsResource;
use App\Livewire\Dashboard\Ths\Actions\AssignTicketAction;
use App\Traits\{FilamentDateHandler, FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior};
use App\Filament\Pages\EditRecord;

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

    protected function afterSave(): void
    {
        if ($this->record->wasChanged(['assigned_to', 'priority'])) {
            app(AssignTicketAction::class)->syncForAdmin($this->record, $this->record->assigned_to);
        }

        if ($this->record->wasChanged('status') && $this->record->status === 'closed') {
            app(AssignTicketAction::class)->markLinkedTaskDone($this->record);
        }
    }
}
