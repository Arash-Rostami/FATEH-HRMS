<?php

namespace App\Filament\Resources\ReservationPolicyResource\Pages;

use App\Filament\Resources\ReservationPolicyResource;
use App\Models\ReservationPolicy;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPolicy extends EditRecord
{
    use FilamentPageBehavior;

    protected static string $resource = ReservationPolicyResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return ReservationPolicy::where('resource_type', $key)->firstOrFail();
    }

    protected function fillForm(): void
    {
        $type = $this->record->resource_type;
        $policies = ReservationPolicy::where('resource_type', $type)->pluck('value', 'key')->toArray();

        $allowedHours = $policies['allowed_hours'] ?? [];
        $flat = collect($policies)->except('allowed_hours')->toArray();
        $flat['allowed_hours_start'] = $allowedHours['start'] ?? null;
        $flat['allowed_hours_end'] = $allowedHours['end'] ?? null;

        $this->form->fill($flat);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $type = $record->resource_type;

        $allowedHours = [
            'start' => $data['allowed_hours_start'] ?? null,
            'end'   => $data['allowed_hours_end'] ?? null,
        ];

        $keys = collect($data)
            ->except(['allowed_hours_start', 'allowed_hours_end'])
            ->put('allowed_hours', $allowedHours);

        $keys->each(function ($value, $key) use ($type) {
            ReservationPolicy::updateOrCreate(
                ['resource_type' => $type, 'key' => $key],
                ['value' => $value]
            );
        });

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('resources/policy/strings.saved');
    }

    protected function getRedirectUrl(): string
    {
        return ReservationPolicyResource::getUrl('index');
    }
}
