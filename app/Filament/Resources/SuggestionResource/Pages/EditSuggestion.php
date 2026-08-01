<?php

namespace App\Filament\Resources\SuggestionResource\Pages;

use App\Filament\Resources\SuggestionResource;
use App\Traits\FilamentEditHeading;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditSuggestion extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = SuggestionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['departments'])) {
            $departments = collect($data['departments']);

            $data['departments'] = $departments->take(1)
                ->merge($departments->skip(1)->filter(fn($dept) => $dept !== 'MA'))
                ->unique()
                ->values()
                ->all();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->load('reviews')->syncStage();
    }
}
