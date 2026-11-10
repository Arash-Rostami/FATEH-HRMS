<?php

namespace App\Filament\Resources\LetterheadResource\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\LetterheadResource;
use App\Traits\Filament\{FilamentHeaderActions, FilamentModelRuleNotice};
use Illuminate\Validation\ValidationException;

class ListLetterheads extends ListRecords
{
    use FilamentHeaderActions {
        getHeaderActions as private baseHeaderActions;
    }
    use FilamentModelRuleNotice;

    protected static string $resource = LetterheadResource::class;

    public function updateTableColumnState(string $column, string $record, mixed $input): mixed
    {
        try {
            return parent::updateTableColumnState($column, $record, $input);
        } catch (ValidationException $exception) {
            return ['error' => collect($exception->errors())->flatten()->first()];
        }
    }

    protected function getHeaderActions(): array
    {
        return [...LetterheadResource::switchActions(static::$resource), ...$this->baseHeaderActions()];
    }
}
