<?php

namespace App\Filament\Resources\ProfileResource\Schemas;

use App\Filament\Resources\ProfileResource\Enums\Degree;
use App\Filament\Resources\ProfileResource\Enums\EmploymentStatus;
use App\Filament\Resources\ProfileResource\Enums\EmploymentType;
use App\Filament\Resources\ProfileResource\Enums\Gender;
use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Filament\Resources\ProfileResource\Exports\ProfileExporter;
use App\Models\Department;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Cache;

class ProfileTablePresenter
{

    public static function avatar(): ImageColumn
    {
        return ImageColumn::make('image')
            ->label(__('resources/profile/strings.table.avatar'))
            ->circular()
            ->disk('public')
            ->imageSize(40)
            ->defaultImageUrl(
                fn($record): string => 'https://ui-avatars.com/api/?name='
                    . urlencode($record->user?->name ?? 'User')
                    . '&background=0D8ABC&color=fff&size=128'
            )
            ->toggleable(isToggledHiddenByDefault: false);
    }


    public static function cellphone(): TextColumn
    {
        return TextColumn::make('cellphone')
            ->label(__('resources/profile/strings.table.cellphone'))
            ->copyable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/profile/strings.table.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function degreeFilter(): SelectFilter
    {
        return SelectFilter::make('degree')
            ->label(__('resources/profile/strings.table.filter_degree'))
            ->options(Degree::class);
    }

    public static function department(): TextColumn
    {
        return TextColumn::make('department.name')
            ->label(__('resources/profile/strings.table.department'))
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label(__('resources/profile/strings.table.filter_department'))
            ->options(fn() => Cache::remember(
                'department_filter_options', now()->addDay(),
                fn() => Department::orderBy('name')->pluck('name', 'code')->toArray()
            ));
    }

    public static function employmentStatus(): TextColumn
    {
        return TextColumn::make('employment_status')
            ->label(__('resources/profile/strings.table.employment_status'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => EmploymentStatus::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => EmploymentStatus::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state): string => EmploymentStatus::tryFrom($state)?->getIcon() ?? '')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function employmentStatusFilter(): SelectFilter
    {
        return SelectFilter::make('employment_status')
            ->label(__('resources/profile/strings.table.filter_employment_status'))
            ->options(EmploymentStatus::class);
    }

    public static function employmentType(): TextColumn
    {
        return TextColumn::make('employment_type')
            ->label(__('resources/profile/strings.table.employment_type'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => EmploymentType::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => EmploymentType::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state): string => EmploymentType::tryFrom($state)?->getIcon() ?? '')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function employmentTypeFilter(): SelectFilter
    {
        return SelectFilter::make('employment_type')
            ->label(__('resources/profile/strings.table.filter_employment_type'))
            ->options(EmploymentType::class);
    }

    public static function gender(): TextColumn
    {
        return TextColumn::make('gender')
            ->label(__('resources/profile/strings.table.gender'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => Gender::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => Gender::tryFrom($state)?->getColor() ?? 'gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function genderFilter(): SelectFilter
    {
        return SelectFilter::make('gender')
            ->label(__('resources/profile/strings.table.filter_gender'))
            ->options(Gender::class);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/profile/strings.table.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function personnelId(): TextColumn
    {
        return TextColumn::make('personnel_id')
            ->label(__('resources/profile/strings.table.personnel_id'))
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function position(): TextColumn
    {
        return TextColumn::make('position')
            ->label(__('resources/profile/strings.table.position'))
            ->formatStateUsing(fn(string $state): string => Position::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => Position::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state): string => Position::tryFrom($state)?->getIcon() ?? '')
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function startDate(): TextColumn
    {
        return TextColumn::make('start_date')
            ->label(__('resources/profile/strings.table.start_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function userName(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/profile/strings.table.user'))
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentGroup(): Group
    {
        return Group::make('department.name')
            ->label(__('resources/profile/strings.form.department_id'))
            ->getTitleFromRecordUsing(fn($record): string => $record->department?->description ?? $record->department?->code ?? '-')
            ->collapsible();
    }

    public static function positionGroup(): Group
    {
        return Group::make('position')
            ->label(__('resources/profile/strings.form.position'))
            ->getTitleFromRecordUsing(fn($record): string => Position::tryFrom($record->position)?->getLabel() ?? $record->position ?? '-')
            ->collapsible();
    }

    public static function employmentStatusGroup(): Group
    {
        return Group::make('employment_status')
            ->label(__('resources/profile/strings.form.employment_status'))
            ->getTitleFromRecordUsing(fn($record): string => EmploymentStatus::tryFrom($record->employment_status)?->getLabel() ?? $record->employment_status ?? '-')
            ->collapsible();
    }

    public static function employmentTypeGroup(): Group
    {
        return Group::make('employment_type')
            ->label(__('resources/profile/strings.form.employment_type'))
            ->getTitleFromRecordUsing(fn($record): string => EmploymentType::tryFrom($record->employment_type)?->getLabel() ?? $record->employment_type ?? '-')
            ->collapsible();
    }

    public static function genderGroup(): Group
    {
        return Group::make('gender')
            ->label(__('resources/profile/strings.form.gender'))
            ->getTitleFromRecordUsing(fn($record): string => Gender::tryFrom($record->gender)?->getLabel() ?? $record->gender ?? '-')
            ->collapsible();
    }
}
