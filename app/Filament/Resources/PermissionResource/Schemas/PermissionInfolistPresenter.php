<?php

namespace App\Filament\Resources\PermissionResource\Schemas;

use App\Models\Permission;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;

class PermissionInfolistPresenter
{
    public static function abilities(): TextEntry
    {
        return TextEntry::make('abilities')
            ->label(__('resources/permission/strings.fields.abilities'))
            ->columnSpanFull()
            ->state(function ($record): string {
                $modules = Permission::availableModules();
                $rows = collect($record->abilities ?? [])->map(function ($row) use ($modules) {
                    $label = $modules[$row['module'] ?? ''] ?? ($row['module'] ?? '?');
                    $actions = collect($row['actions'] ?? [])
                        ->map(fn($a) => __("resources/permission/strings.actions.{$a}"))
                        ->implode('، ');
                    return "<strong>{$label}</strong>: {$actions}";
                });
                return $rows->isEmpty() ? '-' : $rows->implode('<br>');
            })
            ->html();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/permission/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function excludedModules(): TextEntry
    {
        return TextEntry::make('excluded_modules')
            ->label(__('resources/permission/strings.fields.excluded_modules'))
            ->columnSpanFull()
            ->visible(fn($record) => (bool)$record?->is_super_admin)
            ->state(function ($record): string {
                $modules = Permission::availableModules();
                $list = collect($record->excluded_modules ?? [])
                    ->map(fn($k) => $modules[$k] ?? $k);
                return $list->isEmpty() ? '-' : $list->implode(' | ');
            });
    }

    public static function isSuperAdmin(): IconEntry
    {
        return IconEntry::make('is_super_admin')
            ->label(__('resources/permission/strings.fields.is_super_admin'))
            ->boolean();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/permission/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/permission/strings.fields.user'))
            ->weight(FontWeight::Bold);
    }
}
