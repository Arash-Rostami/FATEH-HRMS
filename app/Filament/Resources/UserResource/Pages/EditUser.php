<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Schemas\UserFormPresenter;
use App\Traits\FilamentEditHeading;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;
use Filament\Actions\CreateAction;

class EditUser extends EditRecord
{
    use FilamentEditHeading, FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->label(__('resources/general/strings.table.action_create')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['booking']) && is_array($data['booking'])) {
            $data['booking'] = UserFormPresenter::normalizeBookingState($data['booking']);
        }

        if (is_array($data['extra'] ?? null)) {
            $bucket = $data['extra']['admin'] ?? [];
            if (!array_key_exists('admin', $data['extra'])) {
                foreach ($data['extra'] as $k => $v) {
                    if ($k === 'preferences' || is_array($v)) {
                        continue;
                    }
                    $bucket[$k] = $v;
                }
            }
            $data['extra'] = $bucket;
        }

        return $data;
    }
}
