<?php
namespace App\Filament\Resources\UserResource\Schemas;

use App\Enums\PresenceStatus;
use App\Filament\Resources\UserResource\Enums\UserRole;
use App\Filament\Resources\UserResource\Enums\UserStatus;
use App\Filament\Resources\UserResource\Enums\UserType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;

class UserInfolistPresenter
{
    public static function id(): TextEntry
    {
        return TextEntry::make('id')
            ->label(__('resources/user/strings.infolist.id'))
            ->badge()
            ->color('zinc');
    }

    public static function name(): TextEntry
    {
        return TextEntry::make('name')
            ->label(__('resources/user/strings.infolist.name'))
            ->weight('bold')
            ->badge()
            ->color('primary');
    }

    public static function email(): TextEntry
    {
        return TextEntry::make('email')
            ->label(__('resources/user/strings.infolist.email'))
            ->copyable()
            ->icon('heroicon-o-envelope')
            ->color('info');
    }

    public static function emailVerifiedAt(): TextEntry
    {
        return TextEntry::make('email_verified_at')
            ->label(__('resources/user/strings.infolist.email_verified_at'))
            ->dateTime('Y/m/d H:i')
            ->placeholder('تأیید نشده')
            ->icon('heroicon-o-check-badge')
            ->color(fn ($state) => $state ? 'success' : 'danger');
    }

    public static function type(): TextEntry
    {
        return TextEntry::make('type')
            ->label(__('resources/user/strings.infolist.type'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => UserType::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn (string $state): string => UserType::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn (string $state): string => UserType::tryFrom($state)?->getIcon() ?? '');
    }

    public static function role(): TextEntry
    {
        return TextEntry::make('role')
            ->label(__('resources/user/strings.infolist.role'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn (string $state): string => UserRole::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn (string $state): string => UserRole::tryFrom($state)?->getIcon() ?? '');
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/user/strings.infolist.status'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => UserStatus::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn (string $state): string => UserStatus::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn (string $state): string => UserStatus::tryFrom($state)?->getIcon() ?? '');
    }

    public static function presence(): TextEntry
    {
        return TextEntry::make('presence')
            ->label(__('resources/user/strings.infolist.presence'))
            ->badge()
            ->formatStateUsing(fn ($state): string => $state instanceof PresenceStatus ? $state->label() : ($state ?? '-'))
            ->color(fn ($state): string => $state instanceof PresenceStatus ? $state->color() : 'info');
    }

    public static function maximum(): TextEntry
    {
        return TextEntry::make('maximum')
            ->label(__('resources/user/strings.infolist.maximum'))
            ->numeric()
            ->suffix(' رزرو')
            ->color('warning');
    }

    public static function booking(): RepeatableEntry
    {
        return RepeatableEntry::make('booking')
            ->label(__('resources/user/strings.infolist.booking'))
            ->schema([
                TextEntry::make('key')
                    ->label(__('resources/user/strings.infolist.booking_key'))
                    ->weight('medium')
                    ->color('slate'),
                IconEntry::make('value')
                    ->label(__('resources/user/strings.infolist.booking_value'))
                    ->boolean()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->columns(2)
            ->grid(2)
            ->getStateUsing(fn ($record): array =>
            collect($record->booking ?? [])
                ->map(fn ($v, $k) => ['key' => $k, 'value' => (bool) $v])
                ->values()
                ->toArray()
            )
            ->columnSpanFull();
    }

    public static function extra(): RepeatableEntry
    {
        return RepeatableEntry::make('extra')
            ->label(__('resources/user/strings.infolist.extra'))
            ->schema([
                TextEntry::make('key')
                    ->label(__('resources/user/strings.infolist.extra_key'))
                    ->weight('medium')
                    ->color('slate'),
                TextEntry::make('value')
                    ->label(__('resources/user/strings.infolist.extra_value'))
                    ->placeholder('-')
                    ->color('zinc'),
            ])
            ->columns(2)
            ->getStateUsing(fn ($record): array =>
            collect($record->extra ?? [])
                ->map(fn ($v, $k) => ['key' => $k, 'value' => (string) $v])
                ->values()
                ->toArray()
            )
            ->columnSpanFull();
    }

    public static function lastSeen(): TextEntry
    {
        return TextEntry::make('last_seen')
            ->label(__('resources/user/strings.infolist.last_seen'))
            ->since()
            ->placeholder('-')
            ->color('sky');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/user/strings.infolist.created_at'))
            ->dateTime('Y/m/d H:i')
            ->color('zinc');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/user/strings.infolist.updated_at'))
            ->dateTime('Y/m/d H:i')
            ->color('zinc');
    }
}
