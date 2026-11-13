<?php

namespace App\Support;

use App\Models\Lock;
use App\Models\User;
use Collator;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use WeakMap;

final class LockPolicy
{
    private static ?WeakMap $maps = null;

    private static ?WeakMap $recordMaps = null;

    private static ?array $modules = null;

    public static function isHolder(?User $user): bool
    {
        return Lock::slotFor($user) !== null;
    }

    public static function manages(?User $user, string $slot): bool
    {
        return Lock::slotFor($user, $slot) === $slot;
    }

    public static function blocks(string $module): bool
    {
        if (config('locks.disabled')) {
            return false;
        }

        $user = Auth::user();

        if ($user === null || $user->isDeveloper()) {
            return false;
        }

        $codes = self::map($user)[$module] ?? [];

        if ($codes === [] || self::unlockedBy($module, $codes, $user->id)) {
            return false;
        }

        return !$user->isTopExecutive();
    }

    public static function blocksRecord(string $module, Model $record): bool
    {
        if (config('locks.disabled') || !in_array($module, Lock::RECORD_MODULES, true)) {
            return false;
        }

        $user = Auth::user();

        if ($user === null || $user->isDeveloper()) {
            return false;
        }

        $codes = self::recordMap($user)[$module][$record->getKey()] ?? [];

        if ($codes === [] || (int) $record->getAttribute('owner_id') === $user->id) {
            return false;
        }

        if (self::unlockedBy(self::recordKey($module, $record->getKey()), $codes, $user->id)) {
            return false;
        }

        return !$user->isTopExecutive();
    }

    public static function lockedRecordIds(string $module): array
    {
        $user = Auth::user();

        if (config('locks.disabled') || !in_array($module, Lock::RECORD_MODULES, true) || $user === null || $user->isDeveloper() || $user->isTopExecutive()) {
            return [];
        }

        return collect(self::recordMap($user)[$module] ?? [])
            ->reject(fn (array $codes, int $id): bool => self::unlockedBy(self::recordKey($module, $id), $codes, $user->id))
            ->keys()
            ->all();
    }

    public static function remember(string $module, Lock $lock): void
    {
        $key = $lock->lockable_id > 0 ? self::recordKey($module, $lock->lockable_id) : $module;

        session()->put("locks.{$key}", Auth::id() . ':' . $lock->getKey() . ':' . $lock->fingerprint());
    }

    public static function lockableModules(): array
    {
        if (self::$modules !== null) {
            return self::$modules;
        }

        $modules = collect(Filament::getResources())
            ->filter(fn (string $resource): bool => method_exists($resource, 'moduleKey') && !tenantOffName($resource::getSlug()))
            ->mapWithKeys(fn (string $resource): array => [$resource::moduleKey() => $resource::getPluralModelLabel()])
            ->except(['permission'])
            ->all();

        (new Collator('fa_IR'))->asort($modules);

        return self::$modules = $modules;
    }

    public static function lockedKeys(): array
    {
        $user = Auth::user();

        return $user === null ? [] : array_keys(self::map($user));
    }

    public static function flush(): void
    {
        self::$maps = null;
        self::$recordMaps = null;
        self::$modules = null;
    }

    private static function map(User $user): array
    {
        self::$maps ??= new WeakMap();

        return self::$maps[$user] ??= Lock::moduleMap();
    }

    private static function recordMap(User $user): array
    {
        self::$recordMaps ??= new WeakMap();

        return self::$recordMaps[$user] ??= Lock::recordMap();
    }

    private static function recordKey(string $module, int $recordId): string
    {
        return "{$module}:{$recordId}";
    }

    private static function unlockedBy(string $module, array $codes, int $userId): bool
    {
        [$owner, $id, $fingerprint] = array_pad(explode(':', (string) session("locks.{$module}"), 3), 3, null);

        return (int) $owner === $userId && $fingerprint !== null && ($codes[(int) $id] ?? null) === $fingerprint;
    }
}
