<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\SkillRequestStatus;
use App\Enums\SkillTier;
use App\Models\SkillUser;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SkillsRelationManager extends RelationManager
{
    protected static string $relationship = 'skillUsers';

    public static function getModelLabel(): string
    {
        return __('resources/skill/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/skill/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/skill/strings.plural_label');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->skillUsers()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('skill'))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('skill.name')
                    ->label(__('resources/skill/strings.fields.skill'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->placeholder(fn ($record) => $record->requested_name ?? '-'),
                TextColumn::make('status')
                    ->label(__('resources/skill/strings.fields.status'))
                    ->badge()
                    ->color(fn (SkillRequestStatus $state) => $state->color())
                    ->formatStateUsing(fn (SkillRequestStatus $state) => $state->label())
                    ->sortable(),
                TextColumn::make('tier')
                    ->label(__('resources/skill/strings.fields.tier'))
                    ->badge()
                    ->state(fn (SkillUser $record) => $record->status === SkillRequestStatus::Approved ? $record->stateTier() : null)
                    ->formatStateUsing(fn (?SkillTier $state) => $state?->label() ?? '-')
                    ->icon(fn (?SkillTier $state) => $state?->heroicon())
                    ->color(fn (?SkillTier $state) => $state?->color() ?? 'gray')
                    ->tooltip(fn () => 'تأییدشده: حداقل ' . SkillUser::ENDORSEMENT_SATURATION_CAP . ' تأیید همکار. فعال: استفاده در ' . SkillUser::ACTIVE_WINDOW_DAYS . ' روز اخیر. بدون استفاده: هیچ‌کدام. فقط برای مهارت‌های تأییدشده محاسبه می‌شود.'),
                TextColumn::make('endorsements_count')
                    ->label(__('resources/skill/strings.fields.endorsements_count'))
                    ->formatStateUsing(fn (SkillUser $record) => $record->endorsementLabel())
                    ->sortable(),
                TextColumn::make('last_used_at')
                    ->label(__('resources/skill/strings.fields.last_used_at'))
                    ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '-')
                    ->placeholder('-')
                    ->sortable()
                    ->color('gray'),
                IconColumn::make('is_mentoring')
                    ->label(__('resources/skill/strings.fields.is_mentoring'))
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_private')
                    ->label(__('resources/skill/strings.fields.is_private'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('resources/skill/strings.fields.created_at'))
                    ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '-')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->defaultSort('created_at', 'desc');
    }
}
