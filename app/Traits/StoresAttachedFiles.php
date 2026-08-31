<?php

namespace App\Traits;

use Closure;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;

trait StoresAttachedFiles
{
    protected static function storeAttachment(UploadedFile $file, string $directory, Closure|string|null $fileName = null): array
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_BASENAME);
        $mime = $file->getMimeType() ?? $file->getClientMimeType();
        $size = $file->getSize();

        $resolvedName = is_callable($fileName) ? $fileName($file) : $fileName;

        $path = $resolvedName !== null
            ? $file->storeAs($directory, $resolvedName, 'public')
            : $file->store($directory, 'public');

        if ($path === false) {
            throw new RuntimeException(__('resources/general/strings.errors.file_store_failed'));
        }

        return [
            'path' => $path,
            'name' => $name,
            'mime' => $mime,
            'size' => $size,
        ];
    }

    protected function storeAttachments(array $files, string $directory): array
    {
        $stored = [];

        try {
            foreach ($files as $file) {
                $stored[] = static::storeAttachment($file, $directory);
            }
        } catch (Throwable $e) {
            static::deleteStoredFiles($stored);

            throw $e;
        }

        return $stored;
    }
}
