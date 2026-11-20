<?php

namespace App\Livewire\Dashboard\Launchpad\Actions;

use App\Jobs\RefreshLaunchpadJob;
use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadEditForm;
use App\Models\Launchpad;
use App\Models\User;
use App\Services\Menu\StateService;
use App\Support\LaunchpadAccessPolicy;
use App\Traits\CleansAttachedFiles;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class UpdateLaunchpadAction
{
    use CleansAttachedFiles;

    public function execute(int $id, LaunchpadEditForm $form, User $user): Launchpad
    {
        $guard = Launchpad::query()->find($id, ['id', 'user_id', 'kind', 'accessible_to', 'expire_at', 'source']);

        if ($guard === null || !LaunchpadAccessPolicy::canManage($guard, $user)) {
            throw new RuntimeException('این برنامه در دسترس نیست.');
        }

        $form->kind = (string) $guard->kind;
        $form->storedAuth = $guard->kind === 'static' ? (string) (((array) $guard->source)['auth'] ?? '') : '';
        $form->storedDates = ['accessible_to' => $guard->accessible_to?->toDateString(), 'expire_at' => $guard->expire_at?->toDateString()];
        $form->normalizeSource();
        $form->validate();

        if ($this->sourceChanged((array) $guard->source, $form->source((array) $guard->source))) {
            $form->verifyTabs();
        }

        [$row, $dispatch, $flush, $stale] = DB::transaction(function () use ($id, $form, $user): array {
            $row = Launchpad::query()->whereKey($id)->lockForUpdate()->first(['id', 'user_id', 'department_id', 'folder', 'kind', 'title', 'active', 'audience', 'accessible_from', 'accessible_to', 'expire_at', 'refresh_due_at', 'settings', 'source']);

            if ($row === null || !LaunchpadAccessPolicy::canManage($row, $user) || $row->kind !== $form->kind) {
                throw new RuntimeException('این برنامه در دسترس نیست.');
            }

            $wasLive = $row->isLive();
            $before = filled($row->audience);
            $attributes = [
                'description' => filled($form->description) ? $form->description : null,
                'audience' => $form->public ? [] : User::query()->visibleOnBoard()
                    ->whereIn('users.id', array_map('intval', $form->audience))
                    ->where('users.id', '!=', $row->user_id)
                    ->pluck('users.id')
                    ->map(fn ($key) => (int) $key)
                    ->unique()
                    ->values()
                    ->all(),
                'accessible_from' => filled($form->accessible_from) ? $form->accessible_from : null,
                'accessible_to' => filled($form->accessible_to) ? $form->accessible_to : null,
                'expire_at' => filled($form->expire_at) ? $form->expire_at : null,
            ];
            $armed = false;
            $stale = null;

            if ($row->kind === 'static') {
                $stored = (array) $row->source;
                $source = $form->source($stored);
                $this->assertSecrets($form, $source);

                if ($this->sourceChanged($stored, $source)) {
                    $attributes += ['source' => $source, 'refresh_due_at' => $source === null ? null : now()];
                    $armed = $source !== null;

                    if ($source === null && Arr::has((array) $row->settings, 'refresh')) {
                        $attributes['settings'] = Arr::except((array) $row->settings, 'refresh');
                        $name = Launchpad::storedName((string) config('launchpad.refresh.file'));
                        $stale = $row->directory() !== null && $name !== null ? $row->directory() . '/' . $name : null;
                    }
                }
            }

            $settings = (array) ($attributes['settings'] ?? $row->settings);
            $attributes['settings'] = $form->public ? [...$settings, 'public' => true] : Arr::except($settings, 'public');

            $row->fill($attributes);

            if (!$armed && !$wasLive && $row->refresh_due_at !== null && $row->isLive()) {
                $row->refresh_due_at = now();
                $armed = true;
            }

            $row->save();

            return [$row, $armed && $row->isLive(), $before || filled($row->audience), $stale];
        });

        if ($stale !== null) {
            static::deleteStoredFiles($stale);
        }

        if ($flush) {
            StateService::flush();
        }

        if ($dispatch) {
            RefreshLaunchpadJob::dispatch((int) $row->id);
        }

        return $row;
    }

    private function sourceChanged(array $stored, ?array $source): bool
    {
        return $this->withoutNulls($source ?? []) != $this->withoutNulls($stored);
    }

    private function withoutNulls(array $values): array
    {
        return array_filter(
            array_map(fn ($value) => is_array($value) ? $this->withoutNulls($value) : $value, $values),
            fn ($value) => $value !== null
        );
    }

    private function assertSecrets(LaunchpadEditForm $form, ?array $source): void
    {
        foreach ($form->hasSource() ? $form->sourceFields() : [] as $field => $spec) {
            if ($spec['secret'] && $spec['required'] && !filled($source['env'][$field] ?? null)) {
                throw ValidationException::withMessages(["{$form->getPropertyName()}.sourceEnv.{$field}" => __('validation.required', ['attribute' => $spec['label']])]);
            }
        }
    }
}
