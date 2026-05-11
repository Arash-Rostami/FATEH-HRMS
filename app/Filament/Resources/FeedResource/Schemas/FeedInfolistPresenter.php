<?php

namespace App\Filament\Resources\FeedResource\Schemas;

use App\Filament\Resources\FeedResource\Enums\FeedCategory;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;

class FeedInfolistPresenter
{
    public static function category(): TextEntry
    {
        return TextEntry::make('category')
            ->label(__('resources/feed/strings.fields.category'))
            ->getStateUsing(fn($record) => FeedCategory::tryFrom($record->category))
            ->badge()
            ->placeholder('—');
    }

    public static function commentsCount(): TextEntry
    {
        return TextEntry::make('comments_count')
            ->label(__('resources/feed/strings.fields.comments_count'))
            ->getStateUsing(fn($record) => $record->comments()->count())
            ->badge()
            ->color('primary')
            ->icon('heroicon-o-chat-bubble-left');
    }

    public static function content(): TextEntry
    {
        return TextEntry::make('content')
            ->label(__('resources/feed/strings.fields.content'))
            ->html()
            ->columnSpanFull();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/feed/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->columnSpan(2)
            ->icon('heroicon-o-clock');
    }


    public static function mediaImages(): ImageEntry
    {
        return ImageEntry::make('media_images')
            ->label(__('resources/feed/strings.fields.media_images'))
            ->disk('public')
            ->imageHeight(120)
            ->extraAttributes(['class' => 'rounded-lg'])
            ->getStateUsing(fn($record) => $record->images)
            ->columnSpan(2);
    }

    public static function mediaVideosCount(): TextEntry
    {
        return TextEntry::make('media_videos_count')
            ->label(__('resources/feed/strings.fields.media_videos'))
            ->getStateUsing(fn($record): string => count($record->videos) > 0 ? __('resources/feed/strings.fields.video_count', ['count' => count($record->videos)]) : '—')
            ->icon(fn($state) => $state === '—' ? 'heroicon-o-video-camera-slash' : 'heroicon-o-film')
            ->color(fn($state) => $state === '—' ? 'gray' : 'primary')
            ->columnSpan(2)
            ->prefixAction(
                Action::make('preview')
                    ->tooltip('مشاهده ویدیو')
                    ->icon('heroicon-o-play-circle')
                    ->color('primary')
                    ->modalHeading('مشاهده ویدیو')
                    ->modalContent(fn($record) => view('filament.resources.feed.video-preview', ['videos' => $record->videos]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('بستن')
                    ->visible(fn($record) => count($record->videos) > 0)
            );
    }

    public static function pollOptions(): RepeatableEntry
    {
        return RepeatableEntry::make('poll_options')
            ->label(__('resources/feed/strings.fields.poll_options'))
            ->getStateUsing(fn($record) => collect($record->poll_options ?? [])->map(fn($v) => ['option' => $v])->all())
            ->schema([TextEntry::make('option')->hiddenLabel()])
            ->visible(fn($record) => ($record->category?->value ?? $record->category) === FeedCategory::Poll->value)
            ->columnSpanFull();
    }

    public static function reactionsCount(): TextEntry
    {
        return TextEntry::make('reactions_count')
            ->label(__('resources/feed/strings.fields.reactions_count'))
            ->getStateUsing(fn($record) => $record->reactions()->count())
            ->badge()
            ->color('warning')
            ->icon('heroicon-o-face-smile');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/feed/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->columnSpan(2)
            ->icon('heroicon-o-arrow-path');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/feed/strings.fields.creator'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }
}
