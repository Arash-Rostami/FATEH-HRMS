<?php

namespace App\Filament\Resources\AuthorityResource\Pages;

use App\Filament\Resources\AuthorityResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;

class EditAuthority extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = AuthorityResource::class;
}
