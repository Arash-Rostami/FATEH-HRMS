<?php

namespace App\Filament\Resources\PermissionResource\Schemas;

use App\Models\Permission;
use App\Models\User;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class PermissionFormPresenter
{
    use FilamentFormDivider;

    public static function abilities(): Repeater
    {
        return Repeater::make('abilities')
            ->label(__('resources/permission/strings.fields.abilities'))
            ->addActionLabel(__('resources/permission/strings.form.add_module'))
            ->schema([
                Select::make('module')
                    ->label(__('resources/permission/strings.fields.module'))
                    ->options(fn() => Permission::availableModules())
                    ->required()
                    ->searchable()
                    ->distinct()
                    ->live()
                    ,

                CheckboxList::make('actions')
                    ->label(__('resources/permission/strings.fields.actions'))
                    ->options(fn() => self::actionOptions())
                    ->columns(5)
                    ->bulkToggleable()
                    ->required(),
            ])
            ->columns(1)
            ->itemLabel(fn(array $state): ?string => isset($state['module'])
                ? (Permission::availableModules()[$state['module']] ?? $state['module']) : null
            )
            ->collapsible()
            ->reorderable(false)
            ->defaultItems(0)
            ->helperText(__('resources/permission/strings.hints.abilities'));
    }

    public static function excludedModules(): CheckboxList
    {
        return CheckboxList::make('excluded_modules')
            ->label(__('resources/permission/strings.fields.excluded_modules'))
            ->options(fn() => Permission::availableModules())
            ->columns(3)
            ->searchable()
            ->bulkToggleable()
            ->helperText(__('resources/permission/strings.hints.excluded_modules'));
    }

    public static function isSuperAdmin(): Toggle
    {
        return Toggle::make('is_super_admin')
            ->label(__('resources/permission/strings.fields.is_super_admin'))
            ->onIcon('heroicon-m-shield-check')
            ->offIcon('heroicon-m-shield-exclamation')
            ->live()
            ->default(false)
            ->helperText(__('resources/permission/strings.hints.is_super_admin'));
    }

    public static function user(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/permission/strings.fields.user'))
            ->options(fn() => User::getCachedAllOptions())
            ->required()
            ->searchable()
            ->preload()
            ->unique(ignoreRecord: true)
            ->helperText(__('resources/permission/strings.hints.user'))
            ;
    }

    protected static function actionOptions(): array
    {
        $out = [];
        foreach (Permission::ACTIONS as $a) {
            $out[$a] = __("resources/permission/strings.actions.{$a}");
        }
        return $out;
    }
}
