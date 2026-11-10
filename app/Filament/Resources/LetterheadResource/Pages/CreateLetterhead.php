<?php

namespace App\Filament\Resources\LetterheadResource\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\LetterheadResource;
use App\Traits\Filament\{FilamentHeaderActions, FilamentPageBehavior};

class CreateLetterhead extends CreateRecord
{
    use FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = LetterheadResource::class;
}
