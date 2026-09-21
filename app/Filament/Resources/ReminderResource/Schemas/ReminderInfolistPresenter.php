<?php

namespace App\Filament\Resources\ReminderResource\Schemas;

use App\Enums\ReminderRecurrence;
use App\Models\Reminder;
use App\Traits\FilamentFormDivider;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;

class ReminderInfolistPresenter
{
    use FilamentFormDivider;

    public static function channels(): TextEntry
    {
        return TextEntry::make('channels')
            ->label(__('resources/reminder/strings.fields.channels'))
            ->icon('heroicon-o-bell-alert')
            ->badge()
            ->state(function (?Model $record) {
                if (!$record) {
                    return [];
                }

                $channels = $record->channels ?? [];
                $labels = [];

                if (in_array('email', $channels, true)) {
                    $labels[] = __('resources/reminder/strings.channels.email');
                }

                foreach (['badge', 'nudge', 'edge'] as $signal) {
                    if (Reminder::channelEnabled($channels, $signal)) {
                        $labels[] = __('resources/reminder/strings.channels.' . $signal);
                    }
                }

                return $labels;
            })
            ->helperText(__('resources/reminder/strings.hints.channels'))
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function completedAt(): TextEntry
    {
        return TextEntry::make('completed_at')
            ->label(__('resources/reminder/strings.fields.completed_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-check-circle')
            ->helperText(__('resources/reminder/strings.hints.completed_at'))
            ->color('gray')
            ->placeholder('-');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/reminder/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->placeholder('-');
    }

    public static function dueAt(): TextEntry
    {
        return TextEntry::make('due_at')
            ->label(__('resources/reminder/strings.fields.due_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-calendar-days')
            ->placeholder('-');
    }

    public static function id(): TextEntry
    {
        return TextEntry::make('id')
            ->label(__('resources/reminder/strings.fields.id'))
            ->icon('heroicon-o-hashtag')
            ->copyable()
            ->color('gray');
    }

    public static function notes(): TextEntry
    {
        return TextEntry::make('notes')
            ->label(__('resources/reminder/strings.fields.notes'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function recurs(): TextEntry
    {
        return TextEntry::make('recurs')
            ->label(__('resources/reminder/strings.fields.recurs'))
            ->badge()
            ->icon('heroicon-o-arrow-path')
            ->formatStateUsing(fn($state) => $state instanceof ReminderRecurrence ? $state->label() : (ReminderRecurrence::tryFrom((string) $state)?->label() ?? $state))
            ->color(fn($state) => ($state instanceof ReminderRecurrence ? $state : ReminderRecurrence::tryFrom((string) $state)) === ReminderRecurrence::None ? 'gray' : 'info');
    }

    public static function snoozedUntil(): TextEntry
    {
        return TextEntry::make('snoozed_until')
            ->label(__('resources/reminder/strings.fields.snoozed_until'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-moon')
            ->color('gray')
            ->placeholder('-');
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/reminder/strings.fields.title'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/reminder/strings.fields.user'))
            ->formatStateUsing(fn(?Model $record): string => $record?->user?->name
                ?? __('resources/reminder/strings.deleted_user'))
            ->icon('heroicon-o-user')
            ->placeholder('-');
    }
}
