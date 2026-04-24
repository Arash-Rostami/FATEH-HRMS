<?php

namespace App\Filament\Resources\ProfileResource\Exports;

use App\Filament\Resources\ProfileResource\Enums\Degree;
use App\Filament\Resources\ProfileResource\Enums\EmploymentStatus;
use App\Filament\Resources\ProfileResource\Enums\EmploymentType;
use App\Filament\Resources\ProfileResource\Enums\Gender;
use App\Filament\Resources\ProfileResource\Enums\MaritalStatus;
use App\Models\Profile;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ProfileExporter extends Exporter
{
    protected static ?string $model = Profile::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'department']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources/profile/strings.export.id')),

            ExportColumn::make('personnel_id')
                ->label(__('resources/profile/strings.export.personnel_id'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('user.name')
                ->label(__('resources/profile/strings.export.user_name')),

            ExportColumn::make('position')
                ->label(__('resources/profile/strings.export.position'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('department.name')
                ->label(__('resources/profile/strings.export.department'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('gender')
                ->label(__('resources/profile/strings.export.gender'))
                ->formatStateUsing(fn (?string $state): string => $state ? (Gender::tryFrom($state)?->getLabel() ?? $state) : '-'),

            ExportColumn::make('employment_type')
                ->label(__('resources/profile/strings.export.employment_type'))
                ->formatStateUsing(fn (?string $state): string => $state ? (EmploymentType::tryFrom($state)?->getLabel() ?? $state) : '-'),

            ExportColumn::make('employment_status')
                ->label(__('resources/profile/strings.export.employment_status'))
                ->formatStateUsing(fn (?string $state): string => $state ? (EmploymentStatus::tryFrom($state)?->getLabel() ?? $state) : '-'),

            ExportColumn::make('marital_status')
                ->label(__('resources/profile/strings.export.marital_status'))
                ->formatStateUsing(fn (?string $state): string => $state ? (MaritalStatus::tryFrom($state)?->getLabel() ?? $state) : '-'),

            ExportColumn::make('number_of_children')
                ->label(__('resources/profile/strings.export.number_of_children'))
                ->formatStateUsing(fn (?int $state): string => $state !== null ? (string) $state : '-'),

            ExportColumn::make('id_card_number')
                ->label(__('resources/profile/strings.export.id_card_number'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('id_booklet_number')
                ->label(__('resources/profile/strings.export.id_booklet_number'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('degree')
                ->label(__('resources/profile/strings.export.degree'))
                ->formatStateUsing(fn (?string $state): string => $state ? (Degree::tryFrom($state)?->getLabel() ?? $state) : '-'),

            ExportColumn::make('field')
                ->label(__('resources/profile/strings.export.field'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('birthdate')
                ->label(__('resources/profile/strings.export.birthdate'))
                ->formatStateUsing(fn ($state): string => $state?->format('Y/m/d') ?? '-'),

            ExportColumn::make('age')
                ->label(__('resources/profile/strings.export.age'))
                ->state(fn (Profile $record): string => $record->birthdate
                    ? (string) $record->birthdate->age
                    : '-'
                ),

            ExportColumn::make('cellphone')
                ->label(__('resources/profile/strings.export.cellphone'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('landline')
                ->label(__('resources/profile/strings.export.landline'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('emergency_phone')
                ->label(__('resources/profile/strings.export.emergency_phone'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('emergency_relationship')
                ->label(__('resources/profile/strings.export.emergency_relationship'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('license_plate')
                ->label(__('resources/profile/strings.export.license_plate'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('zip_code')
                ->label(__('resources/profile/strings.export.zip_code'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('address')
                ->label(__('resources/profile/strings.export.address'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('accessibility')
                ->label(__('resources/profile/strings.export.accessibility'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('insurance')
                ->label(__('resources/profile/strings.export.insurance'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('start_date')
                ->label(__('resources/profile/strings.export.start_date'))
                ->formatStateUsing(fn ($state): string => $state?->format('Y/m/d') ?? '-'),

            ExportColumn::make('end_date')
                ->label(__('resources/profile/strings.export.end_date'))
                ->formatStateUsing(fn ($state): string => $state?->format('Y/m/d') ?? '-'),

            ExportColumn::make('work_experience')
                ->label(__('resources/profile/strings.export.work_experience'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('interests')
                ->label(__('resources/profile/strings.export.interests'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('favorite_colors')
                ->label(__('resources/profile/strings.export.favorite_colors'))
                ->state(fn (Profile $record): string => $record->favorite_colors
                    ? implode('، ', $record->favorite_colors)
                    : '-'
                ),

            ExportColumn::make('about_me')
                ->label(__('resources/profile/strings.export.about_me'))
                ->state(fn (Profile $record): string => $record->about_me
                    ? collect($record->about_me)
                        ->map(fn (array $item): string => ($item['key'] ?? '') . ': ' . ($item['value'] ?? ''))
                        ->implode(' | ')
                    : '-'
                ),

            ExportColumn::make('created_at')
                ->label(__('resources/profile/strings.export.created_at'))
                ->formatStateUsing(fn ($state): string => $state?->format('Y/m/d H:i') ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);
        return "خروجی {$count} پروفایل با موفقیت آماده شد.";
    }
}
