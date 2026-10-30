<?php

namespace App\Models\Concerns\Hooks;

use RuntimeException;

trait HasPermissionHooks
{
    protected static function bootHasPermissionHooks(): void
    {
        static::saving(self::normalizeScopeOnSaving(...));
        static::creating(self::rejectDuplicateOnCreating(...));
    }

    private static function normalizeScopeOnSaving(self $m)
    {
        if ($m->is_super_admin) {
            $m->abilities = null;
        } else {
            $m->excluded_modules = null;
        }
    }

    private static function rejectDuplicateOnCreating(self $permission)
    {
        if (static::where('user_id', $permission->user_id)->exists()) {
            throw new RuntimeException(__('resources/permission/strings.validation.duplicate'));
        }
    }
}
