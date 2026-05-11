<?php

namespace App\Filament\Resources\OnboardingResource\Pages;

use App\Filament\Resources\OnboardingResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListOnboardings extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = OnboardingResource::class;
}
