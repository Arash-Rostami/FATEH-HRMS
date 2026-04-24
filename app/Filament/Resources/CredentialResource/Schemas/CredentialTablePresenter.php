<?php

namespace App\Filament\Resources\CredentialResource\Schemas;

use App\Filament\Resources\CredentialResource\Exports\CredentialExporter;
use App\Models\Credential;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class CredentialTablePresenter
{
    public static function appName(): TextColumn
    {
        return TextColumn::make('app_name')
            ->label(__('resources/credential/strings.fields.app_name'))
            ->sortable()
            ->searchable()
            ->weight(FontWeight::Bold)
            ->icon('heroicon-m-squares-2x2')
            ->color('primary')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function appNameGroup(): Group
    {
        return Group::make('app_name')
            ->label(__('resources/credential/strings.fields.app_name'))
            ->collapsible();
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/credential/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function createdAtFilter(): Filter
    {
        return Filter::make('created_at')
            ->label(__('resources/credential/strings.filters.created_at'))
            ->schema([
                DatePicker::make('from')
                    ->label(__('resources/credential/strings.filters.from'))
                    ->locale('fa')
                    ->native(false),
                DatePicker::make('until')
                    ->label(__('resources/credential/strings.filters.until'))
                    ->locale('fa')
                    ->native(false),
            ])
            ->query(fn(Builder $query, array $data) => $query
                ->when($data['from'], fn(Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
                ->when($data['until'], fn(Builder $q, string $d) => $q->whereDate('created_at', '<=', $d))
            )
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['from']) $indicators[] = __('resources/credential/strings.filters.from') . ': ' . $data['from'];
                if ($data['until']) $indicators[] = __('resources/credential/strings.filters.until') . ': ' . $data['until'];
                return $indicators;
            });
    }


    public static function hasLinkFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_link')
            ->label(__('resources/credential/strings.filters.has_link'))
            ->nullable()
            ->attribute('link');
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function link(): TextColumn
    {
        return TextColumn::make('link')
            ->label(__('resources/credential/strings.fields.link'))
            ->url(fn(Credential $record): ?string => $record->link, shouldOpenInNewTab: true)
            ->copyable()
            ->copyMessage(__('resources/credential/strings.notifications.link_copied'))
            ->copyMessageDuration(1500)
            ->icon('heroicon-m-link')
            ->color('info')
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function owner(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/credential/strings.fields.user'))
            ->sortable()
            ->searchable()
            ->icon('heroicon-m-user')
            ->color('info')
            ->visible(fn() => auth()->user()?->isAdmin())
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function passwordColumn(): TextColumn
    {
        return TextColumn::make('password')
            ->label(__('resources/credential/strings.fields.password'))
            ->copyable()
            ->copyMessage(__('resources/credential/strings.notifications.password_copied'))
            ->copyMessageDuration(1500)
            ->fontFamily(FontFamily::Mono)
            ->icon('heroicon-m-lock-closed')
            ->color('warning')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/credential/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->visible(fn() => auth()->user()?->isAdmin());
    }

    public static function userGroup(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/credential/strings.fields.user'))
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Credential $record): string => $record->user?->name ?? '-');
    }

    public static function username(): TextColumn
    {
        return TextColumn::make('username')
            ->label(__('resources/credential/strings.fields.username'))
            ->sortable()
            ->searchable()
            ->copyable()
            ->copyMessage(__('resources/credential/strings.notifications.username_copied'))
            ->copyMessageDuration(1500)
            ->icon('heroicon-m-at-symbol')
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
