<?php

namespace App\Filament\Resources\FAQResource\Pages;

use App\Filament\Resources\FAQResource;
use App\Traits\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;

class CreateFAQ extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = FAQResource::class;
}
