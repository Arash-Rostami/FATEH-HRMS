<?php

namespace App\Filament\Resources\AuthorityResource\Pages;

use App\Filament\Resources\AuthorityResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListAuthorities extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = AuthorityResource::class;
}
