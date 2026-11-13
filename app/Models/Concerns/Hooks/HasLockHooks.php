<?php

namespace App\Models\Concerns\Hooks;

use App\Services\Cache\ModelCacheVersion;
use App\Support\LockPolicy;
use Illuminate\Support\Facades\DB;

trait HasLockHooks
{
    protected static function bootHasLockHooks(): void
    {
        static::saved(self::refreshPolicy(...));
        static::deleted(self::refreshPolicy(...));
    }

    private static function refreshPolicy(): void
    {
        LockPolicy::flush();

        DB::afterCommit(function (): void {
            ModelCacheVersion::bump(static::class);
            LockPolicy::flush();
        });
    }
}
