<?php

namespace App\Filament\Resources\NotifyRuleResource\Schemas;

use App\Enums\NotifyEvent;
use App\Models\Department;
use App\Models\NotifyRule;
use App\Models\User;
use App\Services\Notify\NotifyConditions;
use App\Services\Notify\NotifyRegistry;
use App\Traits\FilamentFormDivider;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;

class NotifyRuleInfolistPresenter
{
    use FilamentFormDivider;

    public static function activeFrom(): TextEntry
    {
        return self::dateEntry('active_from', 'heroicon-o-play');
    }

    public static function activeUntil(): TextEntry
    {
        return self::dateEntry('active_until', 'heroicon-o-stop');
    }

    public static function channels(): TextEntry
    {
        return TextEntry::make('channels')
            ->label(__('resources/notify_rule/strings.fields.channels'))
            ->badge()
            ->formatStateUsing(fn ($state) => NotifyRegistry::channel((string) $state)?->label() ?? $state)
            ->color('info')
            ->placeholder('-');
    }

    public static function conditions(): TextEntry
    {
        return TextEntry::make('conditions')
            ->label(__('resources/notify_rule/strings.fields.conditions'))
            ->state(fn (?Model $record) => NotifyConditions::summary((string) $record?->model, $record?->conditions))
            ->listWithLineBreaks()
            ->placeholder(__('resources/notify_rule/strings.conditions.none'))
            ->columnSpanFull();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/notify_rule/strings.fields.created_at'))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->placeholder('-');
    }

    public static function events(): TextEntry
    {
        return TextEntry::make('events')
            ->label(__('resources/notify_rule/strings.fields.events'))
            ->badge()
            ->formatStateUsing(fn ($state) => NotifyEvent::tryFrom((string) $state)?->label() ?? $state)
            ->color('gray')
            ->placeholder('-');
    }

    public static function extraContacts(): TextEntry
    {
        return TextEntry::make('extra.contacts')
            ->label(__('resources/notify_rule/strings.fields.extra_contacts'))
            ->badge()
            ->color('gray')
            ->extraAttributes(['dir' => 'ltr'])
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function id(): TextEntry
    {
        return TextEntry::make('id')
            ->label(__('resources/notify_rule/strings.fields.id'))
            ->icon('heroicon-o-hashtag')
            ->iconPosition(IconPosition::After)
            ->copyable()
            ->color('gray');
    }

    public static function isActive(): IconEntry
    {
        return IconEntry::make('is_active')
            ->label(__('resources/notify_rule/strings.fields.is_active'))
            ->boolean();
    }

    public static function lines(): TextEntry
    {
        return TextEntry::make('lines')
            ->label(__('resources/notify_rule/strings.fields.lines'))
            ->listWithLineBreaks()
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function logsCount(): TextEntry
    {
        return TextEntry::make('logs_count')
            ->label(__('resources/notify_rule/strings.fields.logs_count'))
            ->numeric()
            ->icon('heroicon-o-paper-airplane')
            ->iconPosition(IconPosition::After)
            ->placeholder('0');
    }

    public static function model(): TextEntry
    {
        return TextEntry::make('model')
            ->label(__('resources/notify_rule/strings.fields.model'))
            ->badge()
            ->formatStateUsing(fn ($state) => $state === NotifyRule::ONE_OFF
                ? __('resources/notify_rule/strings.manual.label')
                : NotifyRegistry::trackables()[$state] ?? class_basename((string) $state))
            ->color(fn ($state) => $state === NotifyRule::ONE_OFF ? 'warning' : 'primary');
    }

    public static function name(): TextEntry
    {
        return TextEntry::make('name')
            ->label(__('resources/notify_rule/strings.fields.name'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->placeholder('-');
    }

    public static function recipients(): TextEntry
    {
        return TextEntry::make('user_ids')
            ->label(__('resources/notify_rule/strings.fields.recipients'))
            ->badge()
            ->color('gray')
            ->state(function (?Model $record): array {
                $names = User::getCachedAllOptions();
                $audience = $record?->extra['audience'] ?? 'users';
                $groups = [];

                if ($audience !== 'users') {
                    $groups[] = __("resources/notify_rule/strings.audience.{$audience}");
                }

                if ($audience === 'departments') {
                    $departments = Department::getCachedOptions();
                    $groups = [...$groups, ...collect($record->extra['departments'] ?? [])->map(fn ($id) => $departments->get($id, $id))->all()];
                }

                return [...$groups, ...collect($record?->user_ids ?? [])->map(fn ($id) => $names->get($id, "#{$id}"))->all()];
            })
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function subject(): TextEntry
    {
        return TextEntry::make('subject')
            ->label(__('resources/notify_rule/strings.fields.subject'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder('-');
    }

    private static function dateEntry(string $column, string $icon): TextEntry
    {
        return TextEntry::make($column)
            ->label(__("resources/notify_rule/strings.fields.{$column}"))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon($icon)
            ->placeholder('-');
    }
}
