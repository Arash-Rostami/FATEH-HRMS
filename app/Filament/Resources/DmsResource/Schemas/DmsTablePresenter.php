<?php

namespace App\Filament\Resources\DmsResource\Schemas;

use App\Filament\Resources\DmsResource\Enums\DocumentStatus;
use App\Models\Department;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class DmsTablePresenter
{
    public static function code(): TextColumn
    {
        return TextColumn::make('code')
            ->label(__('resources/dms/strings.fields.code'))
            ->searchable()
            ->sortable()
            ->copyable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/dms/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function owners(): TextColumn
    {
        return TextColumn::make('owners_display')
            ->label(__('resources/dms/strings.fields.owners'))
            ->getStateUsing(fn($record) => in_array('ALL', $record->owners ?? [])
                ? __('resources/dms/strings.fields.all_departments') : count($record->owners ?? []) . ' ' . __('resources/dms/strings.fields.departments')
            )
            ->badge()
            ->color(fn($record) => in_array('ALL', $record->owners ?? []) ? 'success' : 'primary')
            ->tooltip(fn($record) => $record->getDepartmentTooltipLabels() ?: '—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function ownersFilter(): Filter
    {
        $departmentOptions = Department::getCachedOptions();

        return Filter::make('owners')
            ->label(__('resources/dms/strings.filters.owners'))
            ->schema([
                Select::make('departments')
                    ->label(__('resources/dms/strings.fields.owners'))
                    ->options($departmentOptions)
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->query(fn(Builder $query, array $data) => $query
                ->when($data['departments'] ?? null, function (Builder $query, array $codes) {
                    $query->where(function (Builder $inner) use ($codes) {
                        $inner->orWhereJsonContains('owners', 'ALL');
                        foreach ($codes as $code) {
                            $inner->orWhereJsonContains('owners', $code);
                        }
                    });
                })
            )
            ->indicateUsing(function (array $data) use ($departmentOptions): array {
                if (empty($data['departments'])) return [];

                $names = collect($data['departments'])
                    ->map(fn($code) => $departmentOptions[$code] ?? $code)
                    ->implode(' ┆ ');

                return [__('resources/dms/strings.fields.owners') . ': ' . $names];
            });
    }

    public static function ownersGroup(): Group
    {
        return Group::make('owners')
            ->label(__('resources/dms/strings.fields.owners'))
            ->getTitleFromRecordUsing(
                fn($record): string => in_array('ALL', $record->owners ?? [])
                    ? __('resources/dms/strings.fields.all_departments') : ($record->getDepartmentDisplayLabels() ?: '—')
            )
            ->getKeyFromRecordUsing(fn($record): string => implode(',', $record->owners ?? []))
            ->orderQueryUsing(fn(Builder $query) => $query)
            ->scopeQueryByKeyUsing(function (Builder $query, $key) {
                $expr = self::ownersKeyExpression();

                if ($key === '' || $key === null) {
                    return $query->whereRaw("({$expr}) IS NULL");
                }

                return $query->whereRaw("({$expr}) = ?", [$key]);
            })
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    private static function ownersKeyExpression(): string
    {
        $rows = array_map(fn(int $n): string => "SELECT {$n} AS n", range(0, 31));
        $numbers = '(' . implode(' UNION ALL ', $rows) . ') AS numbers';

        return "(SELECT GROUP_CONCAT(JSON_UNQUOTE(JSON_EXTRACT(owners, CONCAT('$[', n, ']'))) ORDER BY n SEPARATOR ',')"
            . " FROM {$numbers} WHERE JSON_EXTRACT(owners, CONCAT('$[', n, ']')) IS NOT NULL)";
    }

    public static function readCount(): TextColumn
    {
        return TextColumn::make('combined_read_count')
            ->label(__('resources/dms/strings.fields.read_count'))
            ->badge()
            ->color('success')
            ->tooltip(fn($record) => $record->reader_names_tooltip)
            ->default(0)
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/dms/strings.fields.status'))
            ->getStateUsing(fn($record) => DocumentStatus::tryFrom($record->status))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function statusGroup(): Group
    {
        return Group::make('status')
            ->label(__('resources/dms/strings.fields.status'))
            ->getTitleFromRecordUsing(
                fn($record): string => DocumentStatus::tryFrom($record?->status)?->getLabel()
                    ?? (string)$record?->status
            )
            ->getKeyFromRecordUsing(fn($record): string => (string)$record?->status)
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/dms/strings.fields.title'))
            ->searchable()
            ->sortable()
            ->limit(50)
            ->tooltip(fn($state) => strlen($state ?? '') > 50 ? $state : null)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function type(): IconColumn
    {
        return IconColumn::make('type')
            ->label(__('resources/dms/strings.fields.type'))
            ->boolean();
    }

    public static function typeFilter(): TernaryFilter
    {
        return TernaryFilter::make('type')
            ->label(__('resources/dms/strings.fields.type'));
    }

    public static function usersCount(): TextColumn
    {
        return TextColumn::make('users_count')
            ->label(__('resources/dms/strings.fields.users_count'))
            ->getStateUsing(fn($record) => count($record->users ?? []))
            ->badge()
            ->color('info')
            ->tooltip(fn($record) => $record?->user_names_tooltip)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function version(): TextColumn
    {
        return TextColumn::make('version')
            ->label(__('resources/dms/strings.fields.version'))
            ->badge()
            ->color('gray')
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
