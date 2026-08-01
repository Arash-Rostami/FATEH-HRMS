<?php

namespace App\Filament\Resources\PermissionResource\Schemas;

use App\Filament\Resources\PermissionResource\Schemas\Grouping\ModuleGroup;
use App\Models\Permission;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Model;

class PermissionTablePresenter
{
    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/permission/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function isSuperAdmin(): IconColumn
    {
        return IconColumn::make('is_super_admin')
            ->label(__('resources/permission/strings.fields.is_super_admin'))
            ->boolean()
            ->sortable();
    }

    public static function moduleGroup(): Group
    {
        return ModuleGroup::make();
    }

    public static function modulesCount(): TextColumn
    {
        return TextColumn::make('modules_count')
            ->label(__('resources/permission/strings.fields.modules_count'))
            ->state(fn(Model $record): int => $record->is_super_admin
                ? max(0, count(Permission::availableModules()) - count($record->excluded_modules ?? []))
                : count($record->abilities ?? []))
            ->badge()
            ->color('info')
            ->icon('heroicon-m-squares-2x2');
    }

    public static function superOnlyFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_super_admin')
            ->label(__('resources/permission/strings.filters.super_only'));
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/permission/strings.fields.user'))
            ->sortable()
            ->searchable(['user.name', 'user.email'])
            ->weight(FontWeight::Bold)
            ->description(fn(?Model $record): ?string => $record?->user?->email);
    }
}
