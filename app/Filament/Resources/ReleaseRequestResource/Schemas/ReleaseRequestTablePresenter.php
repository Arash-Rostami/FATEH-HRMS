<?php

namespace App\Filament\Resources\ReleaseRequestResource\Schemas;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Filament\Resources\ReleaseRequestResource;
use App\Models\ReleaseRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;

class ReleaseRequestTablePresenter
{
    public static function reject(): Action
    {
        return Action::make('reject')
            ->tooltip(__('resources/release_request/strings.action.reject'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->iconButton()
            ->visible(fn(ReleaseRequest $record) => $record->status !== ReleaseRequestStatus::Rejected->value
                && ReleaseRequestResource::canEdit($record))
            ->modalHeading(__('resources/release_request/strings.action.reject_heading'))
            ->schema([
                Textarea::make('response')
                    ->label(__('resources/release_request/strings.fields.response'))
                    ->nullable()
                    ->maxLength(1000)
                    ->rows(3)
                    ->placeholder(__('resources/release_request/strings.placeholders.response')),
            ])
            ->action(function (ReleaseRequest $record, array $data): void {
                abort_unless(ReleaseRequestResource::canEdit($record), 403);

                $record->update([
                    'status' => ReleaseRequestStatus::Rejected->value,
                    'response' => $data['response'] ?? null,
                ]);

                Notification::make()
                    ->title(__('resources/release_request/strings.notifications.rejected'))
                    ->success()
                    ->send();
            });
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/release_request/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/release_request/strings.fields.status'))
            ->badge()
            ->color(fn($state) => ReleaseRequestStatus::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn($state) => ReleaseRequestStatus::tryFrom($state)?->getIcon() ?? null)
            ->formatStateUsing(fn($state) => ReleaseRequestStatus::tryFrom($state)?->getLabel() ?? $state)
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/release_request/strings.fields.status'))
            ->options(ReleaseRequestStatus::options());
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/release_request/strings.fields.title'))
            ->limit(60)
            ->tooltip(fn($state) => strlen((string) $state) > 60 ? $state : null)
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function type(): TextColumn
    {
        return TextColumn::make('type')
            ->label(__('resources/release_request/strings.fields.type'))
            ->badge()
            ->color(fn($state) => ReleaseRequestType::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn($state) => ReleaseRequestType::tryFrom($state)?->getIcon() ?? null)
            ->formatStateUsing(fn($state) => ReleaseRequestType::tryFrom($state)?->getLabel() ?? $state)
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function typeFilter(): SelectFilter
    {
        return SelectFilter::make('type')
            ->label(__('resources/release_request/strings.fields.type'))
            ->options(ReleaseRequestType::options());
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/release_request/strings.fields.user'))
            ->formatStateUsing(fn(?Model $record): string => $record?->user?->name
                ?? __('resources/release_request/strings.deleted_user'))
            ->sortable()
            ->searchable()
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/release_request/strings.filters.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload();
    }
}