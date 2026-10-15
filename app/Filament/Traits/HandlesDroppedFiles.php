<?php

namespace App\Filament\Traits;

use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Model;

trait HandlesDroppedFiles
{
    use CleansAttachedFiles;

    protected function trackedFileColumns(): array
    {
        return [];
    }

    protected function trackedFilePaths(Model $record, array $tracked): array
    {
        $paths = [];

        foreach ($tracked as $column => $pathKeys) {
            [$relation, $attribute] = str_contains($column, '.') ? explode('.', $column, 2) : [null, $column];
            $model = $relation ? $record->{$relation}()->first() : $record;
            array_push($paths, ...static::extractStoredPaths($model?->{$attribute}, $pathKeys));
        }

        return array_values(array_unique($paths));
    }

    protected function deleteDroppedFiles(Model $record, array $before, array $tracked): void
    {
        if ($before === [] || !$fresh = $record->fresh()) {
            return;
        }

        static::deleteStoredFiles(array_values(array_diff($before, $this->trackedFilePaths($fresh, $tracked))));
    }

    protected function updateDeletingDroppedFiles(Model $record, array $data, array $tracked): Model
    {
        $before = $this->trackedFilePaths($record, $tracked);
        $record->update($data);
        $this->deleteDroppedFiles($record, $before, $tracked);

        return $record;
    }
}
