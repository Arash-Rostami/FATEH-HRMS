<?php

namespace App\Filament\Resources\PermissionResource\Schemas\Grouping;

use App\Models\Permission;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

final class ModuleGroup
{
    private const SUPER_KEY = '__super__';
    private const NONE_KEY = '__none__';

    private const EXPR = "CASE WHEN is_super_admin = 1 THEN '" . self::SUPER_KEY . "'"
    . " ELSE JSON_UNQUOTE(JSON_EXTRACT(abilities, '$[0].module')) END";

    public static function make(): Group
    {
        return Group::make('module_key')
            ->label(__('resources/permission/strings.fields.module'))
            ->groupQueryUsing(
                static fn(Builder $q): Builder => $q
                    ->addSelect('permissions.*')
                    ->selectRaw(self::EXPR . ' as module_key')
                    ->groupByRaw(self::EXPR)
            )
            ->orderQueryUsing(
                static fn(Builder $q, string $direction): Builder => $q
                    ->orderByRaw(self::EXPR . ' ' . (strtolower($direction) === 'desc' ? 'desc' : 'asc'))
            )
            ->scopeQueryByKeyUsing(
                static fn(Builder $q, string $key): Builder => $key === self::NONE_KEY
                    ? $q->whereRaw(self::EXPR . ' IS NULL')
                    : $q->whereRaw(self::EXPR . ' = ?', [$key])
            )
            ->getKeyFromRecordUsing(
                static fn($record): string => $record->is_super_admin
                    ? self::SUPER_KEY
                    : ($record->abilities[0]['module'] ?? self::NONE_KEY)
            )
            ->getTitleFromRecordUsing(
                static fn($record): string => self::title($record)
            )
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    private static function title($record): string
    {
        if ($record->is_super_admin) {
            return __('resources/permission/strings.fields.is_super_admin');
        }

        $key = $record->abilities[0]['module'] ?? null;

        return $key === null
            ? '—'
            : (Permission::availableModules()[$key] ?? $key);
    }
}
