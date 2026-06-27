<?php

namespace App\Traits;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait AuthorizesByPermission
{
    public static function canCreate(): bool
    {
        return static::permits('create');
    }

    public static function canDelete(Model $record): bool
    {
        return static::permits('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::permits('delete');
    }

    public static function canEdit(Model $record): bool
    {
        return static::permits('update');
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::permits('delete');
    }

    public static function canForceDeleteAny(): bool
    {
        return static::permits('delete');
    }

    public static function canRestore(Model $record): bool
    {
        return static::permits('restore');
    }

    public static function canRestoreAny(): bool
    {
        return static::permits('restore');
    }

    public static function canView(Model $record): bool
    {
        return static::permits('view');
    }

    public static function canViewAny(): bool
    {
        return static::permits('view');
    }

    public static function moduleKey(): string
    {
        $base = class_basename(static::class);
        // Strip the trailing "Resource" suffix so ResourceResource -> "resource", not "".
        $english = Str::endsWith($base, 'Resource') ? substr($base, 0, -strlen('Resource')) : $base;

        return Str::snake($english);
    }

    protected static function permits(string $action): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Developers are super-admin by role — every module/action, no row.
        if ($user->isDeveloper()) return true;

        return (bool)Permission::forUser($user->id)?->can(static::moduleKey(), $action);
    }
}
