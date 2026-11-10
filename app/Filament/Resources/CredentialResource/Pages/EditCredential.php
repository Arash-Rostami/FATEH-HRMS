<?php

namespace App\Filament\Resources\CredentialResource\Pages;

use App\Filament\Resources\CredentialResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use App\Filament\Pages\EditRecord;

class EditCredential extends EditRecord
{
    use FilamentEditHeading, FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = CredentialResource::class;

}
