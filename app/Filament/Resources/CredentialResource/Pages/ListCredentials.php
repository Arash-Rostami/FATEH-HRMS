<?php

namespace App\Filament\Resources\CredentialResource\Pages;

use App\Filament\Resources\CredentialResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListCredentials extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = CredentialResource::class;
}
