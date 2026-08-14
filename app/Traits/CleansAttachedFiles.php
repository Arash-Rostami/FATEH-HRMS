<?php

namespace App\Traits;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

trait CleansAttachedFiles
{
    protected static function deleteStoredFiles(mixed $value, array $pathKeys = ['path']): void
    {
        $paths = static::extractStoredPaths($value, $pathKeys);

        if ($paths === []) {
            return;
        }

        try {
            Storage::disk('public')->delete($paths);
        } catch (Throwable $e) {
            Log::warning('CleansAttachedFiles: failed to delete stored file(s)', [
                'model' => static::class,
                'paths' => $paths,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected static function deleteStoredDirectory(string $directory): void
    {
        $directory = trim($directory, "/\\ \t\n\r\0\x0B");

        if ($directory === '' || str_contains($directory, '..') || str_contains($directory, "\0")) {
            return;
        }

        try {
            Storage::disk('public')->deleteDirectory($directory);
        } catch (Throwable $e) {
            Log::warning('CleansAttachedFiles: failed to delete stored directory', [
                'model' => static::class,
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected static function extractStoredPaths(mixed $value, array $pathKeys = ['path']): array
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if ($value === null || $value === '') {
            return [];
        }

        $items = is_array($value)
            ? (array_is_list($value) ? $value : [$value])
            : [$value];

        $paths = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                if (static::isSafeStoredPath($item)) {
                    $paths[] = $item;
                }
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            foreach ($pathKeys as $key) {
                $path = $item[$key] ?? null;
                if (is_string($path) && static::isSafeStoredPath($path)) {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    protected static function isSafeStoredPath(string $path): bool
    {
        $trimmed = trim($path);

        return $trimmed !== ''
            && !str_starts_with($trimmed, '/')
            && !str_starts_with($trimmed, '\\')
            && !str_contains($trimmed, '..')
            && !str_contains($trimmed, "\0")
            && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $trimmed)
            && !preg_match('#^[a-z]:#i', $trimmed);
    }
}
