<?php

namespace App\Filament\Resources\ProjectResource\Schemas;

use App\Models\Department;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class ProjectFormPresenter
{
    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/project/strings.fields.name'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/project/strings.hints.name'));
    }

    public static function owner(): Select
    {
        return Select::make('owner_id')
            ->label(__('resources/project/strings.fields.owner'))
            ->relationship('owner', 'name')
            ->searchable()
            ->preload()
            ->required();
    }

    public static function memberIds(): Select
    {
        return Select::make('member_ids')
            ->label(__('resources/project/strings.fields.member_ids'))
            ->multiple()
            ->options(fn() => User::getCachedActiveOptions())
            ->searchable()
            ->preload()
            ->helperText(__('resources/project/strings.hints.member_ids'));
    }

    public static function departments(): Select
    {
        return Select::make('departments')
            ->label(__('resources/project/strings.fields.departments'))
            ->multiple()
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->helperText(__('resources/project/strings.hints.departments'));
    }

    public static function settings(): Section
    {
        return Section::make(__('resources/project/strings.form.section_settings'))
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Toggle::make('settings.requires_approval')
                    ->label(__('resources/project/strings.fields.requires_approval'))
                    ->helperText(__('resources/project/strings.hints.requires_approval')),

                TextInput::make('settings.sla')
                    ->label(__('resources/project/strings.fields.sla'))
                    ->numeric()
                    ->minValue(1)
                    ->nullable()
                    ->helperText(__('resources/project/strings.hints.sla')),

                DatePicker::make('settings.deadline')
                    ->label(__('resources/project/strings.fields.deadline'))
                    ->nullable()
                    ->helperText(__('resources/project/strings.hints.deadline')),

                Repeater::make('settings.custom_schema')
                    ->label(__('resources/project/strings.fields.custom_schema'))
                    ->helperText(__('resources/project/strings.hints.custom_schema'))
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('key')
                            ->label(__('resources/project/strings.fields.schema_key'))
                            ->required()
                            ->distinct()
                            ->rules(['regex:/^[a-z0-9_]+$/'])
                            ->maxLength(40),
                        TextInput::make('label')
                            ->label(__('resources/project/strings.fields.schema_label'))
                            ->required()
                            ->maxLength(80),
                    ])
                    ->formatStateUsing(fn($state) => collect($state ?? [])
                        ->map(fn($item, $key) => ['key' => $key, 'label' => $item['label'] ?? $key])
                        ->values()
                        ->all())
                    ->dehydrateStateUsing(fn($state) => collect($state ?? [])
                        ->mapWithKeys(fn($item) => [$item['key'] => ['label' => $item['label']]])
                        ->all())
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
