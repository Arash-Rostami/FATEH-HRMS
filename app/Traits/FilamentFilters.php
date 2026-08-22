<?php

namespace App\Traits;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait FilamentFilters
{
    public static function createdAtFilter(): Filter
    {
        return self::jalaliDateRangeFilter(
            'created_at',
            'created_at',
            __('resources/general/strings.filters.date_range'),
            __('resources/general/strings.filters.date_from'),
            __('resources/general/strings.filters.date_until'),
        );
    }

    public static function jalaliDateRangeFilter(string $name, string $column, string $label, string $fromLabel, string $untilLabel): Filter
    {
        $persian = Auth::user()?->getPreference('persian_dates', true) ?? true;

        return Filter::make($name)
            ->label($label)
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('from')
                        ->when($persian, fn(DatePicker $picker) => $picker->jalali(), fn(DatePicker $picker) => $picker->native(false))
                        ->label($fromLabel),
                    DatePicker::make('until')
                        ->when($persian, fn(DatePicker $picker) => $picker->jalali(), fn(DatePicker $picker) => $picker->native(false))
                        ->label($untilLabel),
                ])
            ])
            ->query(fn(Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn($q) => $q->whereDate($column, '>=', $data['from']))
                ->when($data['until'] ?? null, fn($q) => $q->whereDate($column, '<=', $data['until'])))
            ->indicateUsing(function (array $data) use ($persian, $fromLabel, $untilLabel): array {
                $indicators = [];
                if ($from = $data['from'] ?? null) $indicators[] = $fromLabel . ': ' . ($persian ? jdateOnly($from) : $from);
                if ($until = $data['until'] ?? null) $indicators[] = $untilLabel . ': ' . ($persian ? jdateOnly($until) : $until);
                return $indicators;
            });
    }
}
