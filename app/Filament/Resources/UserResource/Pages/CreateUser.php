<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateUser extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = UserResource::class;

    protected bool $emailTrusted = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->emailTrusted = (bool) auth()->user()?->hasElevatedRole() && (bool) ($data['email_trusted'] ?? false);

        unset($data['email_trusted']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->emailTrusted) {
            $record = $this->getRecord();
            $record->forceFill(['email_verified_at' => now()])->saveQuietly();

            Log::info('email trust', ['actor' => auth()->id(), 'user' => $record->getKey(), 'trusted' => true]);
        }
    }
}
