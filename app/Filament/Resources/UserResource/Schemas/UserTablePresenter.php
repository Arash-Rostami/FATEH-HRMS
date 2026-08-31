<?php

namespace App\Filament\Resources\UserResource\Schemas;

use App\Enums\PresenceStatus;
use App\Filament\Resources\UserResource\Enums\UserRole;
use App\Filament\Resources\UserResource\Enums\UserStatus;
use App\Filament\Resources\UserResource\Enums\UserType;
use App\Models\User;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;

class UserTablePresenter
{
    public static function avatar(): ImageColumn
    {
        return ImageColumn::make('profile_image')
            ->label(__('resources/user/strings.table.avatar'))
            ->getStateUsing(fn(User $record): ?string => $record->getProfileImageUrl())
            ->circular()
            ->imageSize(40)
            ->defaultImageUrl(fn(User $record): string => $record->getInitialsAvatarUrl())
            ->toggleable(isToggledHiddenByDefault: false);
    }


    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/user/strings.table.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true)
            ->color('zinc');
    }

    public static function email(): TextColumn
    {
        return TextColumn::make('email')
            ->label(__('resources/user/strings.table.email'))
            ->sortable()
            ->searchable()
            ->copyable()
            ->toggleable(isToggledHiddenByDefault: true)
            ->color('info');
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/user/strings.table.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false)
            ->color('zinc');
    }

    public static function lastSeen(): TextColumn
    {
        return TextColumn::make('last_seen')
            ->label(__('resources/user/strings.table.last_seen'))
            ->formatStateUsing(fn($state) => $state ? toJalaliRelative($state) : '—')
            ->sortable()
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: true)
            ->color('sky');
    }

    public static function maximum(): TextColumn
    {
        return TextColumn::make('maximum')
            ->label(__('resources/user/strings.table.maximum'))
            ->numeric()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true)
            ->color('warning');
    }

    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('resources/user/strings.table.name'))
            ->sortable()
            ->searchable()
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->toggleable(isToggledHiddenByDefault: false)
            ->color('primary');
    }

    public static function presence(): TextColumn
    {
        return TextColumn::make('presence')
            ->label(__('resources/user/strings.table.presence'))
            ->badge()
            ->formatStateUsing(fn($state): string => $state instanceof PresenceStatus ? $state->label() : ($state ?? '-'))
            ->color(fn($state): string => $state instanceof PresenceStatus ? $state->color() : 'info')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function presenceFilter(): SelectFilter
    {
        return SelectFilter::make('presence')
            ->label(__('resources/user/strings.table.filter_presence'))
            ->options(
                collect(PresenceStatus::cases())
                    ->mapWithKeys(fn(PresenceStatus $c) => [$c->value => $c->label()])
            );
    }

    public static function presenceGroup(): Group
    {
        return Group::make('presence')
            ->label(__('resources/user/strings.table.filter_presence'))
            ->getTitleFromRecordUsing(fn(User $record): string => $record->presence instanceof PresenceStatus ? $record->presence->label() : ($record->presence ?? '-'))
            ->collapsible();
    }

    public static function role(): TextColumn
    {
        return TextColumn::make('role')
            ->label(__('resources/user/strings.table.role'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => UserRole::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => UserRole::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state): string => UserRole::tryFrom($state)?->getIcon() ?? '')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function roleFilter(): SelectFilter
    {
        return SelectFilter::make('role')
            ->label(__('resources/user/strings.table.filter_role'))
            ->options(UserRole::class);
    }

    public static function roleGroup(): Group
    {
        return Group::make('role')
            ->label(__('resources/user/strings.table.filter_role'))
            ->getTitleFromRecordUsing(fn(User $record): string => UserRole::tryFrom($record->role)?->getLabel() ?? $record->role ?? '-')
            ->collapsible();
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/user/strings.table.status'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => UserStatus::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => UserStatus::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state): string => UserStatus::tryFrom($state)?->getIcon() ?? '')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/user/strings.table.filter_status'))
            ->options(UserStatus::class);
    }

    public static function statusGroup(): Group
    {
        return Group::make('status')
            ->label(__('resources/user/strings.table.filter_status'))
            ->getTitleFromRecordUsing(fn(User $record): string => UserStatus::tryFrom($record->status)?->getLabel() ?? $record->status ?? '-')
            ->collapsible();
    }

    public static function type(): TextColumn
    {
        return TextColumn::make('type')
            ->label(__('resources/user/strings.table.type'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => UserType::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => UserType::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state): string => UserType::tryFrom($state)?->getIcon() ?? '')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function typeFilter(): SelectFilter
    {
        return SelectFilter::make('type')
            ->label(__('resources/user/strings.table.filter_type'))
            ->options(UserType::class);
    }
}
