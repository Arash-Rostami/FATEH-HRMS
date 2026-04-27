<?php

namespace App\Filament\Resources\AuthorityResource\Schemas\Grouping;

use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class JsonEnumGroup
{
    public static function make(string $field, string $enumClass, string $label): Group
    {
        $expr = "JSON_UNQUOTE(JSON_EXTRACT(details, '$.{$field}'))";

        return Group::make($field)
            ->label($label)
            ->groupQueryUsing(
                fn(Builder $q) => $q->groupByRaw($expr)
            )
            ->orderQueryUsing(
                fn(Builder $q, string $direction) => $q->orderByRaw("{$expr} {$direction}")
            )
            ->scopeQueryByKeyUsing(
                fn(Builder $q, string $key) => $key === '__null__'
                    ? $q->whereRaw("{$expr} IS NULL OR {$expr} = ''")
                    : $q->whereRaw("{$expr} = ?", [$key])
            )
            ->getKeyFromRecordUsing(
                fn($record) => $record->details[$field] ?? '__null__'
            )
            ->getTitleFromRecordUsing(
                fn($record) => $enumClass::tryFrom($record->details[$field] ?? '')
                    ?->getLabel()
                    ?? __('resources/authority/strings.fields.not_set')
            )
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }
}
