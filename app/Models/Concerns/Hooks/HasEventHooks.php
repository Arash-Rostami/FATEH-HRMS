<?php

namespace App\Models\Concerns\Hooks;

use App\Services\ProjectTask\EventSyncService;

trait HasEventHooks
{
    protected static function bootHasEventHooks(): void
    {
        static::updating(self::guardTaskEventOnChange(...));
        static::deleting(self::guardTaskEventOnChange(...));
    }

    private static function guardTaskEventOnChange(self $event)
    {
        return self::$taskSyncing || !EventSyncService::isTaskEvent($event->getOriginal('description'));
    }
}
