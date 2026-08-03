<?php

namespace App\Filament\Resources\SkillRequestResource\Schemas;

use App\Enums\SkillRequestStatus;
use App\Enums\SkillTier;
use App\Models\Department;
use App\Models\SkillUser;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class SkillRequestTablePresenter
{
    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/skill_request/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department')
            ->label(__('resources/skill_request/strings.filters.department'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->query(fn(Builder $query, array $data) => $query->when(
                filled($data['value'] ?? null),
                fn($q) => $q->whereHas('user.profile', fn($p) => $p->where('department_id', $data['value']))
            ));
    }

    public static function endorsementsCount(): TextColumn
    {
        return TextColumn::make('endorsements_count')
            ->label(__('resources/skill_request/strings.fields.endorsements_count'))
            ->formatStateUsing(fn(SkillUser $record) => $record->endorsementLabel())
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function tier(): TextColumn
    {
        return TextColumn::make('tier')
            ->label(__('resources/skill_request/strings.fields.tier'))
            ->badge()
            ->state(fn(SkillUser $record) => $record->status === SkillRequestStatus::Approved ? $record->stateTier() : null)
            ->formatStateUsing(fn(?SkillTier $state) => $state?->label() ?? '-')
            ->icon(fn(?SkillTier $state) => $state?->heroicon())
            ->color(fn(?SkillTier $state) => $state?->color() ?? 'gray')
            ->tooltip(fn() => 'تأییدشده: حداقل ' . SkillUser::ENDORSEMENT_SATURATION_CAP . ' تأیید همکار. فعال: استفاده در ' . SkillUser::ACTIVE_WINDOW_DAYS . ' روز اخیر. بدون استفاده: هیچ‌کدام. فقط برای درخواست‌های تأییدشده محاسبه می‌شود.')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function lastUsedAt(): TextColumn
    {
        return TextColumn::make('last_used_at')
            ->label(__('resources/skill_request/strings.fields.last_used_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->placeholder('-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function skill(): TextColumn
    {
        return TextColumn::make('skill.name')
            ->label(__('resources/skill_request/strings.fields.skill'))
            ->searchable()
            ->sortable()
            ->placeholder(fn($record) => $record->requested_name ?? '-')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function staleFilter(): Filter
    {
        return Filter::make('stale')
            ->label(__('resources/skill_request/strings.filters.stale'))
            ->query(fn(Builder $query) => $query
                ->where('status', SkillRequestStatus::Pending)
                ->where('created_at', '<=', now()->subDays(14)))
            ->toggle();
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/skill_request/strings.fields.status'))
            ->badge()
            ->color(fn(SkillRequestStatus $state) => $state->color())
            ->formatStateUsing(fn(SkillRequestStatus $state) => $state->label())
            ->icon(fn(SkillRequestStatus $state) => $state->heroicon())
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/skill_request/strings.filters.status'))
            ->options(collect(SkillRequestStatus::cases())->mapWithKeys(fn(SkillRequestStatus $s) => [$s->value => $s->label()]))
            ->default(SkillRequestStatus::Pending->value);
    }

    public static function statusFormField(): Select
    {
        return Select::make('status')
            ->label(__('resources/skill_request/strings.fields.status'))
            ->options(collect(SkillRequestStatus::cases())->mapWithKeys(fn(SkillRequestStatus $s) => [$s->value => $s->label()]))
            ->default(SkillRequestStatus::Pending->value);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/skill_request/strings.fields.user'))
            ->weight(FontWeight::Bold)
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
