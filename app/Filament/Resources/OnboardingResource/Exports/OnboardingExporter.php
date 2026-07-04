<?php

namespace App\Filament\Resources\OnboardingResource\Exports;

use App\Models\Onboarding;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class OnboardingExporter extends Exporter
{
    protected static ?string $model = Onboarding::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            ExportColumn::make('user.name')
                ->label(__('resources/onboarding/strings.fields.user_id')),

            ExportColumn::make('is_active')
                ->label(__('resources/onboarding/strings.fields.is_active'))
                ->formatStateUsing(fn($state) => $state ? 'بله' : 'خیر'),

            ExportColumn::make('welcome')
                ->label(__('resources/onboarding/strings.fields.welcome'))
                ->state(fn($record) => $record->welcome ? mb_substr(strip_tags($record->welcome), 0, 500) : ''),

            ExportColumn::make('mission')
                ->label(__('resources/onboarding/strings.fields.mission'))
                ->state(fn($record) => $record->mission ? mb_substr(strip_tags($record->mission), 0, 500) : ''),

            ExportColumn::make('vision')
                ->label(__('resources/onboarding/strings.fields.vision'))
                ->state(fn($record) => $record->vision ? mb_substr(strip_tags($record->vision), 0, 500) : ''),

            ExportColumn::make('schedule')
                ->label(__('resources/onboarding/strings.fields.schedule'))
                ->state(fn($record) => $record->schedule ? mb_substr(strip_tags($record->schedule), 0, 500) : ''),

            ExportColumn::make('videos_count')
                ->label(__('resources/onboarding/strings.fields.videos'))
                ->state(fn($record) => count($record->videos ?? [])),

            ExportColumn::make('videos_titles')
                ->label(__('resources/onboarding/strings.repeater.video_title'))
                ->state(fn($record) => collect($record->videos ?? [])
                    ->pluck('title')
                    ->filter()
                    ->implode(' | ')
                ),

            ExportColumn::make('guides_count')
                ->label(__('resources/onboarding/strings.fields.guides'))
                ->state(fn($record) => count($record->guides ?? [])),

            ExportColumn::make('guides_titles')
                ->label(__('resources/onboarding/strings.repeater.guide_title'))
                ->state(fn($record) => collect($record->guides ?? [])
                    ->pluck('title')
                    ->filter()
                    ->implode(' | ')
                ),

            ExportColumn::make('extras_keys')
                ->label(__('resources/onboarding/strings.fields.extras'))
                ->state(fn($record) => collect((array)($record->extras ?? []))
                    ->keys()
                    ->implode(', ')
                ),

            ExportColumn::make('created_at')
                ->label(__('resources/onboarding/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);
        return __('resources/onboarding/strings.export.completed', ['count' => $count]);
    }
}
