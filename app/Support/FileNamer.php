<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class FileNamer
{
    private const CLIENT_EXTENSION_MODULES = ['onboarding', 'post', 'link', 'feed', 'gallery'];

    public static function prefix(): string
    {
        return strtoupper((string)config('app.tenant'));
    }

    public static function stored(string $module, UploadedFile $file): string
    {
        $prefix = self::prefix() . '-' . strtoupper($module);
        $date = now()->format('Ymd');
        $unique = Str::random(12) . '-' . time();
        $ext = in_array($module, self::CLIENT_EXTENSION_MODULES, true)
            ? ($file->getClientOriginalExtension() ?: $file->extension())
            : ($file->extension() ?: $file->getClientOriginalExtension());

        return "{$prefix}-{$date}-{$unique}.{$ext}";
    }

    public static function download(string $extension, int|string|null ...$parts): string
    {
        $slug = collect([self::prefix(), ...$parts])
            ->filter(fn($part) => filled($part))
            ->map(fn($part) => trim(preg_replace('/[^A-Za-z0-9._]+/', '-', Str::ascii((string)$part)), '-'))
            ->filter()
            ->implode('-');

        return $extension === '' ? $slug : $slug . '.' . strtolower($extension);
    }
}
