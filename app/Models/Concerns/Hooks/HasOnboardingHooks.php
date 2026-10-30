<?php

namespace App\Models\Concerns\Hooks;

trait HasOnboardingHooks
{
    protected static function bootHasOnboardingHooks(): void
    {
        static::deleting(self::deleteMediaFilesOnDeleting(...));
    }

    private static function deleteMediaFilesOnDeleting(self $onboarding)
    {
        static::deleteStoredFiles($onboarding->videos, ['url', 'thumbnail']);
        static::deleteStoredFiles($onboarding->guides, ['url']);
    }
}
