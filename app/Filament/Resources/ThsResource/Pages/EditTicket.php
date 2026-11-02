<?php

namespace App\Filament\Resources\ThsResource\Pages;

use App\Filament\Resources\ThsResource;
use App\Traits\{FilamentDateHandler, FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior};
use App\Filament\Pages\EditRecord;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

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

    private const WORKSPACE_FIELDS = ['status', 'assigned_to', 'effectiveness', 'completion_date', 'action_result'];

    #[Locked]
    public array $shownFields = [];

    protected function afterFill(): void
    {
        $this->rememberShownFields();
    }

    protected function afterSave(): void
    {
        $this->rememberShownFields();
    }

    #[On('ths-ticket-updated')]
    public function syncFromWorkspace(): void
    {
        $this->record->refresh();

        $current = $this->workspaceValues();
        $changed = array_keys(array_filter(
            $current,
            fn ($value, $field) => $value !== ($this->shownFields[$field] ?? null),
            ARRAY_FILTER_USE_BOTH,
        ));

        if ($changed) {
            $this->shownFields = $current;
            $this->refreshFormData(in_array('status', $changed, true) ? [...$changed, 'completion_date'] : $changed);
        }
    }

    private function rememberShownFields(): void
    {
        $this->shownFields = $this->workspaceValues();
    }

    private function workspaceValues(): array
    {
        return Arr::map(
            Arr::only($this->record->getAttributes(), self::WORKSPACE_FIELDS),
            fn ($value) => $value === null ? null : (string) $value,
        );
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mergeDeadline($data);
    }

    protected function trackedFileColumns(): array
    {
        return ['requester_files' => ['file'], 'assignee_files' => ['file']];
    }
}
