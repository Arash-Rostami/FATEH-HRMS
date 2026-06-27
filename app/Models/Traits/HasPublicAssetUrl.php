<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Storage;

trait HasPublicAssetUrl
{
    public static function resolvePublicAssetUrl(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            return $disk->url($path);
        }

        return asset($path);
    }
}