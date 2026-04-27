<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditReport extends EditRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = ReportResource::class;
}
