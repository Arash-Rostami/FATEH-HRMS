<?php

namespace App\Traits\Filament;

use App\Support\LockPolicy;
use BackedEnum;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

trait AuthorizesByPermission
{
    public static function canCreate(): bool
    {
        return static::permits('create');
    }

    public static function canDelete(Model $record): bool
    {
        return static::permits('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return static::permits('delete');
    }

    public static function canEdit(Model $record): bool
    {
        return static::permits('update', $record);
    }

    public static function canEditAny(): bool
    {
        return static::permits('update');
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::permits('delete', $record);
    }

    public static function canForceDeleteAny(): bool
    {
        return static::permits('delete');
    }

    public static function canRestore(Model $record): bool
    {
        return static::permits('restore', $record);
    }

    public static function canRestoreAny(): bool
    {
        return static::permits('restore');
    }

    public static function canView(Model $record): bool
    {
        return static::permits('view', $record);
    }

    public static function canViewAny(): bool
    {
        return static::permits('view');
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof BackedEnum ? $action->value : ($action instanceof UnitEnum ? $action->name : $action);

        $mapped = match ($ability) {
            'viewAny', 'view' => 'view',
            'create', 'replicate' => 'create',
            'update', 'reorder' => 'update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny' => 'delete',
            'restore', 'restoreAny' => 'restore',
            default => null,
        };

        return match ($mapped) {
            'create' => static::canCreate(),
            default => $mapped && static::permits($mapped, $record),
        } ? Response::allow() : Response::deny();
    }

    public static function moduleKey(): string
    {
        $base = class_basename(static::class);
        // Strip the trailing "Resource" suffix so ResourceResource -> "resource", not "".
        $english = Str::endsWith($base, 'Resource') ? substr($base, 0, -strlen('Resource')) : $base;

        return Str::snake($english);
    }

    protected static function permits(string $action, ?Model $record = null): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return !LockPolicy::blocks(static::moduleKey())
            && !($record !== null && LockPolicy::blocksRecord(static::moduleKey(), $record))
            && $user->permits(static::moduleKey(), $action);
    }
}
