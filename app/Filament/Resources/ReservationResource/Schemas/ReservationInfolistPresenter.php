<?php

namespace App\Filament\Resources\ReservationResource\Schemas;

use App\Enums\CancelReason;
use App\Enums\ReservationStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

class ReservationInfolistPresenter
{
    public static function cancelReason(): TextEntry
    {
        return TextEntry::make('cancel_reason')
            ->label(__('resources/reservation/strings.fields.cancel_reason'))
            ->badge()
            ->formatStateUsing(fn(?string $state) => $state ? (CancelReason::tryFrom($state)?->getLabel() ?? $state) : '—')
            ->color('warning')->placeholder('—');
    }

    public static function cancelledAt(): TextEntry
    {
        return TextEntry::make('cancelled_at')
            ->label(__('resources/reservation/strings.fields.cancelled_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->placeholder('—');
    }

    public static function cancelledBy(): TextEntry
    {
        return TextEntry::make('cancelledBy.name')
            ->label(__('resources/reservation/strings.fields.cancelled_by'))
            ->placeholder('—');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/reservation/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->color('gray')->placeholder('-');
    }

    public static function endTime(): TextEntry
    {
        return TextEntry::make('end_time')
            ->label(__('resources/reservation/strings.fields.end_time'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After);
    }

    public static function isFullDay(): TextEntry
    {
        return TextEntry::make('is_full_day')
            ->label(__('resources/reservation/strings.fields.is_full_day'))
            ->badge()
            ->formatStateUsing(fn(bool $state) => $state ? 'تمام‌روز' : 'ساعتی')
            ->color(fn(bool $state) => $state ? 'info' : 'gray');
    }

    public static function occurrencesCount(): TextEntry
    {
        return TextEntry::make('occurrences_count')
            ->label(__('resources/reservation/strings.relations.occurrences'))
            ->badge()->color('info')
            ->state(fn($record): int => $record->occurrences_count ?? $record->occurrences()->count());
    }

    public static function parentId(): TextEntry
    {
        return TextEntry::make('parent.id')
            ->label(__('resources/reservation/strings.fields.parent_id'))
            ->badge()->color('info')->placeholder('—');
    }

    public static function resource(): TextEntry
    {
        return TextEntry::make('resource.labeled_name')
            ->label(__('resources/reservation/strings.fields.resource'));
    }

    public static function startTime(): TextEntry
    {
        return TextEntry::make('start_time')
            ->label(__('resources/reservation/strings.fields.start_time'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After);
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/reservation/strings.fields.status'))
            ->badge()
            ->formatStateUsing(fn(string $state) => ReservationStatus::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state) => ReservationStatus::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state) => ReservationStatus::tryFrom($state)?->getIcon() ?? null);
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/reservation/strings.fields.user'))
            ->weight(FontWeight::Bold);
    }
}
