<?php

namespace App\Traits\Filament;

use App\Filament\Pages\UnlockModules;
use App\Filament\RelationManagers\RelationManager as AppRelationManager;
use App\Support\LockPolicy;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;

trait GuardsModuleLock
{
    public function bootedGuardsModuleLock(): void
    {
        foreach ($this->lockModuleKeys() as $key) {
            abort_if(LockPolicy::blocks($key), 403);
        }

        $this->guardRecordLock();
    }

    protected function guardRecordLock(): void
    {
        $record = $this instanceof RelationManager ? ($this->ownerRecord ?? null) : ($this->record ?? null);

        if (!$record instanceof Model) {
            return;
        }

        $resource = Filament::getModelResource($record);

        if ($resource === null || !method_exists($resource, 'moduleKey') || !LockPolicy::blocksRecord($resource::moduleKey(), $record)) {
            return;
        }

        abort_unless($resource::canAccess() && !request()->hasHeader('X-Livewire'), 403);

        throw new HttpResponseException(new RedirectResponse(UnlockModules::getUrl(['record' => $record->getKey()])));
    }

    protected function lockModuleKeys(): array
    {
        $resources = [];

        if (method_exists(static::class, 'getResource')) {
            $resources[] = static::getResource();
        }

        if ($this instanceof RelationManager) {
            $resources[] = static::getRelatedResource();
            $resources[] = isset($this->ownerRecord) ? Filament::getModelResource($this->ownerRecord) : null;
            $resources[] = isset($this->ownerRecord) && $this instanceof AppRelationManager ? static::relatedModelResource($this->ownerRecord) : null;
        }

        return collect($resources)
            ->filter(fn (?string $resource): bool => $resource !== null && method_exists($resource, 'moduleKey'))
            ->map(fn (string $resource): string => $resource::moduleKey())
            ->unique()
            ->values()
            ->all();
    }
}
