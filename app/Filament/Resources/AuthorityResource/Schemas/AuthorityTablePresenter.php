<?php

namespace App\Filament\Resources\AuthorityResource\Schemas;

use App\Filament\Resources\AuthorityResource\Enums\{DelegationLevel, ExecutionProcedure, ImpactScore, RepeatFrequency};
use App\Filament\Resources\AuthorityResource\Schemas\Grouping\JsonEnumGroup;
use App\Models\Department;
use Filament\Tables\Columns\{TextColumn, ToggleColumn};
use Filament\Tables\Filters\{SelectFilter, TernaryFilter};
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuthorityTablePresenter
{
    public static function approvedDelegation(): TextColumn
    {
        return TextColumn::make('approved_delegation')
            ->label(__('resources/authority/strings.fields.approved_delegation'))
            ->getStateUsing(fn($record) => DelegationLevel::tryFrom($record->details['approved_delegation'] ?? ''))
            ->badge()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function approvedDelegationFilter(): SelectFilter
    {
        return SelectFilter::make('approved_delegation')
            ->label(__('resources/authority/strings.fields.approved_delegation'))
            ->options(collect(DelegationLevel::cases())->mapWithKeys(fn($c) => [$c->value => $c->getLabel()]))
            ->query(fn(Builder $query, array $data) => $data['value']
                ? $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(details, '$.approved_delegation')) = ?", [$data['value']])
                : $query
            );
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/authority/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function delegationGroup(): Group
    {
        return JsonEnumGroup::make(
            field: 'approved_delegation',
            enumClass: DelegationLevel::class,
            label: __('resources/authority/strings.fields.approved_delegation'),
        );
    }

    public static function department(): TextColumn
    {
        return TextColumn::make('department.name')
            ->label(__('resources/authority/strings.fields.department'))
            ->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label(__('resources/authority/strings.fields.department'))
            ->searchable()
            ->preload()
            ->options(fn() => Department::getCachedOptions()->toArray());
    }

    public static function departmentGroup(): Group
    {
        return Group::make('department.description')
            ->label(__('resources/authority/strings.fields.department'))
            ->getTitleFromRecordUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->collapsible();
    }

    public static function duty(): TextColumn
    {
        return TextColumn::make('duty')
            ->label(__('resources/authority/strings.fields.duty'))
            ->getStateUsing(fn($record) => $record->details['duty'] ?? '—')
            ->searchable(query: fn(Builder $query, string $search) => $query->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(details, '$.duty')) LIKE ?", ["%{$search}%"]
            ))
            ->html()
            ->limit(60)
            ->tooltip(fn($state) => strlen($state) > 60 ? strip_tags($state) : null)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function executionProcedureFilter(): SelectFilter
    {
        return SelectFilter::make('execution_procedure')
            ->label(__('resources/authority/strings.fields.execution_procedure'))
            ->options(collect(ExecutionProcedure::cases())->mapWithKeys(fn($c) => [$c->value => $c->getLabel()]))
            ->query(fn(Builder $query, array $data) => $data['value']
                ? $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(details, '$.execution_procedure')) = ?", [$data['value']])
                : $query
            );
    }

    public static function executionProcedureGroup(): Group
    {
        return JsonEnumGroup::make(
            field: 'execution_procedure',
            enumClass: ExecutionProcedure::class,
            label: __('resources/authority/strings.fields.execution_procedure'),
        );
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function impactScore(): TextColumn
    {
        return TextColumn::make('impact_score')
            ->label(__('resources/authority/strings.fields.impact_score'))
            ->getStateUsing(fn($record) => ImpactScore::tryFrom($record->details['impact_score'] ?? ''))
            ->badge()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function impactScoreFilter(): SelectFilter
    {
        return SelectFilter::make('impact_score')
            ->label(__('resources/authority/strings.fields.impact_score'))
            ->options(collect(ImpactScore::cases())->mapWithKeys(fn($c) => [$c->value => $c->getLabel()]))
            ->query(fn(Builder $query, array $data) => $data['value']
                ? $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(details, '$.impact_score')) = ?", [$data['value']])
                : $query
            );
    }

    public static function impactScoreGroup(): Group
    {
        return JsonEnumGroup::make(
            field: 'impact_score',
            enumClass: ImpactScore::class,
            label: __('resources/authority/strings.fields.impact_score'),
        );
    }

    public static function repeatFrequencyFilter(): SelectFilter
    {
        return SelectFilter::make('repeat_frequency')
            ->label(__('resources/authority/strings.fields.repeat_frequency'))
            ->options(collect(RepeatFrequency::cases())->mapWithKeys(fn($c) => [$c->value => $c->getLabel()]))
            ->query(fn(Builder $query, array $data) => $data['value']
                ? $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(details, '$.repeat_frequency')) = ?", [$data['value']])
                : $query
            );
    }

    public static function repeatFrequencyGroup(): Group
    {
        return JsonEnumGroup::make(
            field: 'repeat_frequency',
            enumClass: RepeatFrequency::class,
            label: __('resources/authority/strings.fields.repeat_frequency'),
        );
    }

    public static function subDuty(): ToggleColumn
    {
        return ToggleColumn::make('sub_duty')
            ->label(__('resources/authority/strings.fields.sub_duty'))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function subDutyFilter(): TernaryFilter
    {
        return TernaryFilter::make('sub_duty')
            ->label(__('resources/authority/strings.fields.sub_duty'))
            ->trueLabel(__('resources/authority/strings.filters.sub_duty_true'))
            ->falseLabel(__('resources/authority/strings.filters.sub_duty_false'));
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/authority/strings.fields.user'))
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
