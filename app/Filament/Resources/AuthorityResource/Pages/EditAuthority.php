<?php

namespace App\Filament\Resources\AuthorityResource\Pages;

use App\Filament\Resources\AuthorityResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditAuthority extends EditRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = AuthorityResource::class;
}
