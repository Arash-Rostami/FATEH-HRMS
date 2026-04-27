<?php

namespace App\Filament\Resources\FAQResource\Pages;

use App\Filament\Resources\FAQResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListFAQs extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = FAQResource::class;
}
