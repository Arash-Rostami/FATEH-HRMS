<?php

namespace App\Models\Concerns\Hooks;

use App\Filament\Resources\UserResource\Enums\UserType;

trait HasUserHooks
{
    protected static function bootHasUserHooks(): void
    {
        static::created(self::bumpCacheVersionOnCreated(...));
        static::saved(self::syncCachesAndReleaseWorkOnSaved(...));
        static::deleted(self::bumpCacheVersionOnDeleted(...));
    }

    private static function bumpCacheVersionOnCreated()
    {
        return static::bumpCacheVersion();
    }

    private static function syncCachesAndReleaseWorkOnSaved(self $user)
    {
        if ($user->wasChanged(['name', 'role', 'status', 'type'])) {
            static::bumpCacheVersion();
        }

        self::forgetDynamicGroupCache();

        if (!$user->wasRecentlyCreated && $user->wasChanged(['status', 'type'])
            && $user->getOriginal('status') === 'active' && $user->getOriginal('type') !== UserType::Guest->value
            && ($user->status !== 'active' || $user->type === UserType::Guest->value)) {
            $user->releaseFromProjectWork();
            $user->reconcileDepartmentTickets();
        }
    }

    private static function bumpCacheVersionOnDeleted()
    {
        static::bumpCacheVersion();
        self::forgetDynamicGroupCache();
    }
}
