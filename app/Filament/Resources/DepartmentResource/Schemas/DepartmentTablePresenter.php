<?php

namespace App\Filament\Resources\DepartmentResource\Schemas;

use App\Filament\Resources\DepartmentResource\Schemas\Grouping\UserCountGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DepartmentTablePresenter
{
    public static function code(): TextColumn
    {
        return TextColumn::make('code')
            ->label(__('resources/department/strings.fields.code'))
            ->sortable()
            ->searchable()
            ->badge()
            ->color('primary')
            ->fontFamily(FontFamily::Mono)
            ->copyable()
            ->copyMessageDuration(1500)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/department/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function createdAtFilter(): Filter
    {
        return Filter::make('created_at')
            ->label(__('resources/department/strings.filters.created_at'))
            ->schema([
                DatePicker::make('from')->label(__('resources/department/strings.filters.from'))->native(false),
                DatePicker::make('until')->label(__('resources/department/strings.filters.until'))->native(false),
            ])
            ->query(fn(Builder $q, array $data) => $q
                ->when($data['from'], fn(Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($data['until'], fn(Builder $q, $d) => $q->whereDate('created_at', '<=', $d))
            )
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['from']) $indicators[] = __('resources/department/strings.filters.from') . ': ' . $data['from'];
                if ($data['until']) $indicators[] = __('resources/department/strings.filters.until') . ': ' . $data['until'];
                return $indicators;
            });
    }

    public static function description(): TextColumn
    {
        return TextColumn::make('description')
            ->label(__('resources/department/strings.fields.description'))
            ->limit(60)
            ->tooltip(fn(string $state): string => $state)
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function hasUsersFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_users')
            ->label(__('resources/department/strings.filters.has_users'))
            ->queries(
                true: fn(Builder $q) => $q->has('users'),
                false: fn(Builder $q) => $q->doesntHave('users'),
            );
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('resources/department/strings.fields.name'))
            ->sortable()
            ->formatStateUsing(fn(?Model $record): string => $record?->description ?? $record?->name ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->name ?? $record?->description ?? '-')
            ->searchable(['description', 'name'])
            ->weight(FontWeight::Bold)
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function usersCount(): TextColumn
    {
        return TextColumn::make('users_count')
            ->label(__('resources/department/strings.fields.users_count'))
            ->counts('users')
            ->badge()
            ->color(fn(int $state): string => match (true) {
                $state === 0 => 'danger',
                $state > 1 => 'success',
                default => 'warning'
            })
            ->icon('heroicon-m-users')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function usersCountGroup(): Group
    {
        return UserCountGroup::make();
    }
}
