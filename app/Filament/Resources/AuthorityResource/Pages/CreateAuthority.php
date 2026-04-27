<?php

namespace App\Filament\Resources\AuthorityResource\Pages;

use App\Filament\Resources\AuthorityResource;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateAuthority extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = AuthorityResource::class;
}
