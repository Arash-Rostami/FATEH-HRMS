<?php

namespace App\Filament\Resources\FAQResource\Pages;

use App\Filament\Resources\FAQResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditFAQ extends EditRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = FAQResource::class;
}
