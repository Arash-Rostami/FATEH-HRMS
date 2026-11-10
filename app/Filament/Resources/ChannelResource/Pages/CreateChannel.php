<?php

namespace App\Filament\Resources\ChannelResource\Pages;

use App\Filament\Resources\ChannelResource;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;

class CreateChannel extends CreateRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = ChannelResource::class;
}