<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Storage;

trait HasAvatar
{
    public function resolveImageUrl(?string $image): ?string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '//')) {
            return $image;
        }

        $image = ltrim($image, '/');

        if (str_starts_with($image, 'storage/')) {
            return url('/' . $image);
        }

        if (str_starts_with($image, 'public/')) {
            $image = substr($image, strlen('public/'));
        }

        if (config('filesystems.disks.public.driver') !== 'local') {
            return Storage::disk('public')->url($image);
        }

        return url('/storage/' . $image);
    }

    public function generateInitialsAvatar(?string $name): string
    {
        $name = trim($name ?: 'User');
        $words = preg_split('/\s+/u', $name, flags: PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
        }

        if ($initials === '') {
            $initials = 'U';
        }

        $escapedInitials = htmlspecialchars($initials, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
    <rect width="128" height="128" rx="64" fill="#0D8ABC"/>
    <text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="48" font-weight="700">{$escapedInitials}</text>
</svg>
SVG;

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}
