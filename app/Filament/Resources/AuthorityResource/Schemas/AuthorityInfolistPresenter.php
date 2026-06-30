<?php

namespace App\Filament\Resources\AuthorityResource\Schemas;

use App\Filament\Resources\AuthorityResource\Enums\{DelegationLevel, ExecutionProcedure, ImpactScore, RepeatFrequency};
use Filament\Infolists\Components\{IconEntry, TextEntry};
use Illuminate\Database\Eloquent\Model;

class AuthorityInfolistPresenter
{
    public static function duty(): TextEntry
    {
        return TextEntry::make('duty')
            ->label(__('resources/authority/strings.fields.duty'))
            ->getStateUsing(fn($record) => $record->details['duty'] ?? null)
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function department(): TextEntry
    {
        return TextEntry::make('department.description')
            ->label(__('resources/authority/strings.fields.department'))
            ->placeholder('—')
            ->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-')
            ->icon('heroicon-o-building-office-2');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/authority/strings.fields.user'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function subDuty(): IconEntry
    {
        return IconEntry::make('sub_duty')
            ->label(__('resources/authority/strings.fields.sub_duty'))
            ->boolean();
    }

    public static function executionProcedure(): TextEntry
    {
        return TextEntry::make('execution_procedure')
            ->label(__('resources/authority/strings.fields.execution_procedure'))
            ->getStateUsing(fn($record) => ExecutionProcedure::tryFrom($record->details['execution_procedure'] ?? ''))
            ->badge()
            ->placeholder('—');
    }

    public static function repeatFrequency(): TextEntry
    {
        return TextEntry::make('repeat_frequency')
            ->label(__('resources/authority/strings.fields.repeat_frequency'))
            ->getStateUsing(fn($record) => RepeatFrequency::tryFrom($record->details['repeat_frequency'] ?? ''))
            ->badge()
            ->placeholder('—');
    }

    public static function impactScore(): TextEntry
    {
        return TextEntry::make('impact_score')
            ->label(__('resources/authority/strings.fields.impact_score'))
            ->getStateUsing(fn($record) => ImpactScore::tryFrom($record->details['impact_score'] ?? ''))
            ->badge()
            ->placeholder('—');
    }

    public static function proposedDelegation(): TextEntry
    {
        return TextEntry::make('proposed_delegation')
            ->label(__('resources/authority/strings.fields.proposed_delegation'))
            ->getStateUsing(fn($record) => DelegationLevel::tryFrom($record->details['proposed_delegation'] ?? ''))
            ->badge()
            ->placeholder('—');
    }

    public static function approvedDelegation(): TextEntry
    {
        return TextEntry::make('approved_delegation')
            ->label(__('resources/authority/strings.fields.approved_delegation'))
            ->getStateUsing(fn($record) => DelegationLevel::tryFrom($record->details['approved_delegation'] ?? ''))
            ->badge()
            ->placeholder('—');
    }

    public static function coDelegate(): TextEntry
    {
        return TextEntry::make('co_delegate')
            ->label(__('resources/authority/strings.fields.co_delegate'))
            ->getStateUsing(fn($record) => $record->details['co_delegate'] ?? null)
            ->placeholder('—')
            ->icon('heroicon-o-user-group');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/authority/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/authority/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }
}
