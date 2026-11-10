<?php

namespace App\Filament\Resources\LetterheadResource\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\LetterheadResource;
use App\Traits\Filament\{FilamentEditHeading, FilamentHeaderActions, FilamentModelRuleNotice, FilamentPageBehavior};

class EditLetterhead extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentModelRuleNotice, FilamentPageBehavior;

    protected static string $resource = LetterheadResource::class;
}
