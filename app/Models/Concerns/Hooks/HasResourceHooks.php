<?php

namespace App\Models\Concerns\Hooks;

use App\Enums\ResourceType;
use App\Models\ReservationPolicy;

trait HasResourceHooks
{
    protected static function bootHasResourceHooks(): void
    {
        static::deleting(self::deleteImageOnDeleting(...));
        static::deleted(self::pruneOrphanPolicyOnDeleted(...));
    }

    private static function deleteImageOnDeleting(self $resource)
    {
        return static::deleteStoredFiles($resource->getRawOriginal('image'));
    }

    private static function pruneOrphanPolicyOnDeleted(self $resource): void
    {
        if (! static::where('type', $resource->type)->exists()) {
            ReservationPolicy::where('resource_type', $resource->type)->delete();
            ResourceType::forgetCache();
        }
    }
}
