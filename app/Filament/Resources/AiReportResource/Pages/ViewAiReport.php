<?php

namespace App\Filament\Resources\AiReportResource\Pages;

use App\Filament\Pages\ViewRecord;
use App\Filament\Resources\AiReportResource;
use App\Traits\Filament\FilamentHeaderActions;

class ViewAiReport extends ViewRecord
{
    use FilamentHeaderActions;

    protected static string $resource = AiReportResource::class;
}
