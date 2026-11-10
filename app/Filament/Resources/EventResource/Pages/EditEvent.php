<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;

class EditEvent extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return Event::unpackCountdown($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = static::restoreOmittedCountdownFields(
            $data,
            (array) ($this->getRecord()->countdown ?? []),
        );

        return Event::packCountdown($data);
    }

    protected static function restoreOmittedCountdownFields(array $data, array $storedCountdown): array
    {
        if ((bool) ($data['enabled'] ?? false)) {
            return $data;
        }

        foreach (['mood', 'confetti', 'messages'] as $key) {
            if (! array_key_exists($key, $data) && array_key_exists($key, $storedCountdown)) {
                $data[$key] = $storedCountdown[$key];
            }
        }

        return $data;
    }
}
