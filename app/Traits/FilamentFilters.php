<?php

namespace App\Traits;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;


trait FilamentFilters
{
    public static function createdAtFilter(): Filter
    {
        return Filter::make('created_at')
            ->label(__('resources/general/strings.filters.date_range'))
            ->schema([

                Grid::make(2)->schema([
                    DatePicker::make('from')
                        ->native(false)
                        ->locale('fa')
                        ->label(__('resources/general/strings.filters.date_from')),
                    DatePicker::make('until')
                        ->native(false)
                        ->locale('fa')
                        ->label(__('resources/general/strings.filters.date_until'))
                ])
            ])
            ->query(fn(Builder $query, array $data) => $query
                ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until'])))
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['from']) $indicators[] = __('resources/general/strings.filters.date_from') . ': ' . $data['from'];
                if ($data['until']) $indicators[] = __('resources/general/strings.filters.date_until') . ': ' . $data['until'];
                return $indicators;
            });
    }
}
