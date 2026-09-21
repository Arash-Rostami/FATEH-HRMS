<?php

namespace App\Filament\Resources\SkillResource\Schemas;

use App\Traits\FilamentFormDivider;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;

class SkillInfolistPresenter
{
    use FilamentFormDivider;

    public static function name(): TextEntry
    {
        return TextEntry::make('name')
            ->label(__('resources/skill/strings.fields.name'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->badge()
            ->color('primary')
            ->weight('bold');
    }

    public static function nameEn(): TextEntry
    {
        return TextEntry::make('name_en')
            ->label(__('resources/skill/strings.fields.name_en'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->icon('heroicon-o-language')
            ->placeholder('-');
    }

    public static function category(): TextEntry
    {
        return TextEntry::make('category')
            ->label(__('resources/skill/strings.fields.category'))
            ->badge()
            ->icon('heroicon-o-tag')
            ->color('info')
            ->placeholder('-');
    }

    public static function icon(): TextEntry
    {
        return TextEntry::make('icon')
            ->label(__('resources/skill/strings.fields.icon'))
            ->icon('heroicon-o-swatch')
            ->placeholder('-');
    }

    public static function isActive(): IconEntry
    {
        return IconEntry::make('is_active')
            ->label(__('resources/skill/strings.fields.is_active'))
            ->boolean();
    }

    public static function membersCount(): TextEntry
    {
        return TextEntry::make('skill_users_count')
            ->label(__('resources/skill/strings.fields.members_count'))
            ->state(fn ($record): int => (int) ($record->skill_users_count ?? $record->skillUsers()->count()))
            ->icon('heroicon-o-users')
            ->numeric();
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/skill/strings.fields.description'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->icon('heroicon-o-document-text')
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/skill/strings.fields.created_at'))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->placeholder('-');
    }
}