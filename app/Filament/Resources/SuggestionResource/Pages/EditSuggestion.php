<?php

namespace App\Filament\Resources\SuggestionResource\Pages;

use App\Filament\Resources\SuggestionResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditSuggestion extends EditRecord
{
    use FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = SuggestionResource::class;
}
