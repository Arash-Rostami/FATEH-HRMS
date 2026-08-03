<?php

namespace App\Filament\Resources\SkillResource\Schemas;

use App\Enums\SkillIcon;
use App\Models\Skill;
use App\Rules\UniqueActiveSkillName;
use App\Traits\FilamentFormDivider;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class SkillFormPresenter
{
    use FilamentFormDivider;

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/skill/strings.fields.name'))
            ->required()
            ->maxLength(255)
            ->rules([self::noNameConflictRule()])
            ->placeholder(__('resources/skill/strings.placeholders.name'))
            ->helperText(__('resources/skill/strings.hints.name'));
    }

    public static function nameEn(): TextInput
    {
        return TextInput::make('name_en')
            ->label(__('resources/skill/strings.fields.name_en'))
            ->maxLength(255)
            ->rules([self::noNameConflictRule()])
            ->placeholder(__('resources/skill/strings.placeholders.name_en'));
    }

    private static function noNameConflictRule(): Closure
    {
        return fn (?Skill $record, string $operation): UniqueActiveSkillName => new UniqueActiveSkillName($record, $operation);
    }

    public static function category(): Select
    {
        return Select::make('category')
            ->label(__('resources/skill/strings.fields.category'))
            ->searchable()
            ->options(fn (): array => Skill::whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->toArray())
            ->createOptionForm([
                TextInput::make('category')
                    ->label(__('resources/skill/strings.fields.category'))
                    ->required()
                    ->maxLength(255),
            ])
            ->createOptionUsing(fn (array $data): string => $data['category'])
            ->getOptionLabelUsing(fn ($value) => $value)
            ->nullable();
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/skill/strings.fields.description'))
            ->maxLength(2000)
            ->columnSpanFull()
            ->placeholder(__('resources/skill/strings.placeholders.description'));
    }

    public static function icon(): Select
    {
        return Select::make('icon')
            ->label(__('resources/skill/strings.fields.icon'))
            ->helperText(__('resources/skill/strings.hints.icon'))
            ->searchable()
            ->native(false)
            ->options(fn (?Skill $record): array => collect(SkillIcon::cases())
                ->mapWithKeys(fn (SkillIcon $icon) => [$icon->value => $icon->value])
                ->when(
                    filled($record?->icon) && !SkillIcon::tryFrom($record->icon),
                    fn ($options) => $options->put($record->icon, $record->icon),
                )
                ->toArray())
            ->default(SkillIcon::default()->value)
            ->nullable();
    }

    public static function isActive(): Toggle
    {
        return Toggle::make('is_active')
            ->label(__('resources/skill/strings.fields.is_active'))
            ->default(true);
    }
}