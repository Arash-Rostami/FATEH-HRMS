<?php

namespace App\Filament\Resources\ChannelResource\Pages;

use App\Filament\Resources\ChannelResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditChannel extends EditRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = ChannelResource::class;
}