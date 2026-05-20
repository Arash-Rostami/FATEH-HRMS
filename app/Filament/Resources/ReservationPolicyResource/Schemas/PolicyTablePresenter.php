<?php

namespace App\Filament\Resources\ReservationPolicyResource\Schemas;

use App\Enums\ResourceType;
use App\Models\ReservationPolicy;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;

class PolicyTablePresenter
{
    public static function editAction(string $resourceClass): EditAction
    {
        return EditAction::make()
            ->url(fn(ReservationPolicy $record): string => $resourceClass::getUrl('edit', ['record' => $record->resource_type]))
            ->tooltip(__('resources/general/strings.table.action_edit'))
            ->iconButton();
    }

    public static function lastUpdated(): TextColumn
    {
        return TextColumn::make('last_updated')
            ->label('آخرین ویرایش')
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->color('gray');
    }

    public static function resourceType(): TextColumn
    {
        return TextColumn::make('resource_type')
            ->label(__('resources/policy/strings.fields.resource_type'))
            ->badge()
            ->formatStateUsing(fn(string $state) => ResourceType::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state) => ResourceType::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state) => ResourceType::tryFrom($state)?->getIcon() ?? null)
            ->sortable();
    }

    public static function rulesCount(): TextColumn
    {
        return TextColumn::make('rules_count')
            ->label(__('resources/policy/strings.fields.rules_count'))
            ->badge()
            ->color('info');
    }
}
