<?php

namespace App\Models\Concerns\Hooks;

use App\Services\Menu\StateService;
use App\Traits\CleansAttachedFiles;

trait HasLaunchpadHooks
{
    use CleansAttachedFiles;

    protected static function bootHasLaunchpadHooks(): void
    {
        static::created(self::flushMenuStateWhenShared(...));
        static::updated(fn (self $row) => $row->wasChanged('active') ? self::flushMenuStateWhenShared($row) : null);
        static::deleted(self::deleteFilesAndFlush(...));
    }

    private static function deleteFilesAndFlush(self $row): void
    {
        $row->newQueryWithoutScopes()->whereKey($row->getKey())->toBase()->update(['source' => null, 'refresh_due_at' => null]);

        if (($directory = $row->directory()) !== null) {
            self::deleteStoredDirectory($directory);
        }

        self::flushMenuStateWhenShared($row);
    }

    private static function flushMenuStateWhenShared(self $row): void
    {
        if (filled($row->audience)) {
            StateService::flush();
        }
    }
}
