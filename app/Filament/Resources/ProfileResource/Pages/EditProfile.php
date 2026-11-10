<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use App\Filament\Pages\EditRecord;

class EditProfile extends EditRecord
{
    use FilamentEditHeading, FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = ProfileResource::class;

    protected function trackedFileColumns(): array
    {
        return ['image' => [], 'attachments' => ['path']];
    }
}
