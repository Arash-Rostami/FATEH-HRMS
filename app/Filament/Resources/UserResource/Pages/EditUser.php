<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Schemas\UserFormPresenter;
use App\Models\User;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;
use Filament\Actions\CreateAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    use FilamentEditHeading, FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = UserResource::class;

    protected ?string $previousSignature = null;

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
        if (is_array($data['extra'] ?? null)) {
            $bucket = User::adminBucketOf($data['extra']);
            $data['extra'] = User::editableExtraOf($data['extra']);
            $data['signature_image'] = $bucket['signature'] ?? null;
            $data['registry_permission'] = (bool) ($bucket['correspondence_registry'] ?? false);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $extra = $data['extra'] ?? [];
        $overrides = [];

        $this->previousSignature = $this->storedSignature();

        if (array_key_exists('signature_image', $data)) {
            $overrides['signature'] = $this->trustedSignature($data['signature_image']);
        }

        if (array_key_exists('registry_permission', $data)) {
            $overrides['correspondence_registry'] = $data['registry_permission'] ?: null;
        }

        if (! empty($data['clear_sign_code'])) {
            $overrides['sign_code_hash'] = null;
        } elseif (filled($data['sign_code'] ?? null)) {
            $overrides['sign_code_hash'] = $data['sign_code'];
        }

        foreach ($overrides as $key => $value) {
            $value === null ? Arr::forget($extra, $key) : Arr::set($extra, $key, $value);
        }

        unset($data['signature_image'], $data['registry_permission'], $data['sign_code'], $data['clear_sign_code']);

        return [...$data, 'extra' => $extra];
    }

    protected function afterSave(): void
    {
        $previous = $this->previousSignature;

        if ($previous === null || $previous === $this->storedSignature() || ! self::isSignaturePath($previous)) {
            return;
        }

        DB::afterCommit(fn () => rescue(fn () => Storage::disk('local')->delete($previous)));
    }

    private function storedSignature(): ?string
    {
        $path = $this->getRecord()->getExtraValue('admin.signature');

        return is_string($path) ? $path : null;
    }

    private function trustedSignature(mixed $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if ($path === $this->previousSignature) {
            return $path;
        }

        $trusted = self::isSignaturePath($path)
            && Storage::disk('local')->fileExists($path)
            && ! User::query()->whereKeyNot($this->getRecord()->getKey())->where('extra->admin->signature', $path)->exists();

        if (! $trusted) {
            throw ValidationException::withMessages([
                'data.signature_image' => __('resources/correspondence/strings.admin.fields.signature_invalid'),
            ]);
        }

        return $path;
    }

    private static function isSignaturePath(mixed $path): bool
    {
        return is_string($path)
            && ! str_contains($path, '..')
            && preg_match('#^' . preg_quote(UserFormPresenter::SIGNATURE_DIRECTORY, '#') . '/[A-Za-z0-9._-]+\.(png|jpe?g|webp)$#i', $path) === 1;
    }
}
