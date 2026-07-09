<?php

namespace App\Filament\Resources\UserResource\Exports;

use App\Enums\PresenceStatus;
use App\Filament\Resources\UserResource\Enums\UserRole;
use App\Filament\Resources\UserResource\Enums\UserStatus;
use App\Filament\Resources\UserResource\Enums\UserType;
use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources/user/strings.export.id')),

            ExportColumn::make('name')
                ->label(__('resources/user/strings.export.name')),

            ExportColumn::make('email')
                ->label(__('resources/user/strings.export.email')),

            ExportColumn::make('type')
                ->label(__('resources/user/strings.export.type'))
                ->formatStateUsing(fn (string $state): string => UserType::tryFrom($state)?->getLabel() ?? $state),

            ExportColumn::make('role')
                ->label(__('resources/user/strings.export.role'))
                ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->getLabel() ?? $state),

            ExportColumn::make('status')
                ->label(__('resources/user/strings.export.status'))
                ->formatStateUsing(fn (string $state): string => UserStatus::tryFrom($state)?->getLabel() ?? $state),

            ExportColumn::make('presence')
                ->label(__('resources/user/strings.export.presence'))
                ->formatStateUsing(fn ($state): string => $state instanceof PresenceStatus ? $state->label() : ($state ?? '-')),

            ExportColumn::make('maximum')
                ->label(__('resources/user/strings.export.maximum')),

            ExportColumn::make('last_seen')
                ->label(__('resources/user/strings.export.last_seen'))
                ->formatStateUsing(fn ($state) => $state ? toJalaliSmart($state) : '-'),

            ExportColumn::make('created_at')
                ->label(__('resources/user/strings.export.created_at'))
                ->formatStateUsing(fn ($state) => $state ? toJalaliSmart($state) : '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);
        return "خروجی {$count} کاربر با موفقیت آماده شد.";
    }
}
