<?php

namespace App\Traits;

use Closure;
use Illuminate\Http\UploadedFile;
use RuntimeException;

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
            throw new RuntimeException("Failed to store file in directory: {$directory}");
        }

        return [
            'path' => $path,
            'name' => $name,
            'mime' => $mime,
            'size' => $size,
        ];
    }
}
