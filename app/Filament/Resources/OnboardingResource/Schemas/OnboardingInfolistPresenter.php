<?php

namespace App\Filament\Resources\OnboardingResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;

class OnboardingInfolistPresenter
{
    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/onboarding/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function extras(): RepeatableEntry
    {
        return RepeatableEntry::make('extras_list')
            ->label(__('resources/onboarding/strings.fields.extras'))
            ->getStateUsing(fn($record) => collect((array)($record->extras ?? []))
                ->map(fn($v, $k) => ['key' => $k, 'value' => $v])
                ->values()
                ->all()
            )
            ->schema([
                TextEntry::make('key')
                    ->label(__('resources/onboarding/strings.repeater.extra_key'))
                    ->badge()
                    ->color('gray'),

                TextEntry::make('value')
                    ->label(__('resources/onboarding/strings.repeater.extra_value'))
                    ->html()
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->columnSpanFull();
    }

    public static function guides(): RepeatableEntry
    {
        return RepeatableEntry::make('guides')
            ->label(__('resources/onboarding/strings.fields.guides'))
            ->schema([
                TextEntry::make('title')
                    ->label(__('resources/onboarding/strings.repeater.guide_title'))
                    ->weight(FontWeight::SemiBold),

                TextEntry::make('ext')
                    ->label(__('resources/onboarding/strings.repeater.guide_ext'))
                    ->badge()
                    ->color(fn($state) => match (strtolower($state ?? '')) {
                        'pdf' => 'danger',
                        'docx' => 'primary',
                        'xlsx' => 'success',
                        default => 'gray',
                    }),

                TextEntry::make('size')
                    ->label(__('resources/onboarding/strings.repeater.guide_size'))
                    ->placeholder('—')
                    ->icon('heroicon-o-document'),

                TextEntry::make('url')
                    ->label(__('resources/onboarding/strings.repeater.guide_file'))
                    ->formatStateUsing(fn($state) => $state ? basename($state) : '—')
                    ->url(fn($state) => $state ? Storage::disk('public')->url($state) : null, shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    public static function isActive(): IconEntry
    {
        return IconEntry::make('is_active')
            ->label(__('resources/onboarding/strings.fields.is_active'))
            ->boolean()
            ->trueColor('success')
            ->falseColor('gray');
    }

    public static function mission(): TextEntry
    {
        return TextEntry::make('mission')
            ->label(__('resources/onboarding/strings.fields.mission'))
            ->html()
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function schedule(): TextEntry
    {
        return TextEntry::make('schedule')
            ->label(__('resources/onboarding/strings.fields.schedule'))
            ->html()
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/onboarding/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/onboarding/strings.fields.user_id'))
            ->placeholder(__('resources/onboarding/strings.fields.global_audience'))
            ->icon('heroicon-o-user');
    }

    public static function videos(): RepeatableEntry
    {
        return RepeatableEntry::make('videos')
            ->label(__('resources/onboarding/strings.fields.videos'))
            ->schema([
                TextEntry::make('title')
                    ->label(__('resources/onboarding/strings.repeater.video_title'))
                    ->weight(FontWeight::SemiBold),

                TextEntry::make('duration')
                    ->label(__('resources/onboarding/strings.repeater.video_duration'))
                    ->placeholder('—')
                    ->icon('heroicon-o-clock'),

                TextEntry::make('url')
                    ->label(__('resources/onboarding/strings.repeater.video_url'))
                    ->formatStateUsing(fn($state) => $state ? basename($state) : '—')
                    ->url(fn($state) => $state ? Storage::disk('public')->url($state) : null, shouldOpenInNewTab: true)
                    ->icon('heroicon-o-play-circle')
                    ->color('primary')
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    public static function vision(): TextEntry
    {
        return TextEntry::make('vision')
            ->label(__('resources/onboarding/strings.fields.vision'))
            ->html()
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function welcome(): TextEntry
    {
        return TextEntry::make('welcome')
            ->label(__('resources/onboarding/strings.fields.welcome'))
            ->html()
            ->columnSpanFull();
    }
}
