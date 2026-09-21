<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Traits\FilamentEditHeading;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditProject extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['extraSettings'] = collect($this->getRecord()->otherSettings())
            ->map(fn($value, $key) => ['key' => $key, 'value' => $value])
            ->values()
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $rows = Arr::pull($data, 'extraSettings', []);
        $settings = app(\App\Services\ProjectTask\ProjectSettings::class)
            ->mergeExtra($this->getRecord()->settings ?? [], $rows);

        $data['settings'] = array_merge($settings, $data['settings'] ?? []);

        return $data;
    }
}