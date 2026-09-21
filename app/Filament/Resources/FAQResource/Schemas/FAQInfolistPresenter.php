<?php

namespace App\Filament\Resources\FAQResource\Schemas;

use App\Traits\FilamentFormDivider;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;

class FAQInfolistPresenter
{
    use FilamentFormDivider;

    public static function answer(): TextEntry
    {
        return TextEntry::make('answer')
            ->label(__('resources/faq/strings.fields.answer'))
            ->html()
            ->icon('heroicon-o-chat-bubble-left-right')
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function category(): TextEntry
    {
        return TextEntry::make('category')
            ->label(__('resources/faq/strings.fields.category'))
            ->badge()
            ->icon('heroicon-o-tag')
            ->color('info')
            ->placeholder('-');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/faq/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->placeholder('-');
    }

    public static function department(): TextEntry
    {
        return TextEntry::make('department.name')
            ->label(__('resources/faq/strings.fields.department'))
            ->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-')
            ->icon('heroicon-o-building-office-2')
            ->placeholder('-');
    }

    public static function id(): TextEntry
    {
        return TextEntry::make('id')
            ->label('ID')
            ->badge()
            ->icon('heroicon-o-hashtag')
            ->copyable()
            ->color('gray');
    }

    public static function question(): TextEntry
    {
        return TextEntry::make('question')
            ->label(__('resources/faq/strings.fields.question'))
            ->html()
            ->icon('heroicon-o-question-mark-circle')
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/faq/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->placeholder('-');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/faq/strings.fields.user'))
            ->icon('heroicon-o-user')
            ->placeholder('-');
    }
}
