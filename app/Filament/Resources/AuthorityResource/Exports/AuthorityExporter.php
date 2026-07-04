<?php

namespace App\Filament\Resources\AuthorityResource\Exports;

use App\Filament\Resources\AuthorityResource\Enums\{DelegationLevel, ExecutionProcedure, ImpactScore, RepeatFrequency};
use App\Models\Authority;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class AuthorityExporter extends Exporter
{
    protected static ?string $model = Authority::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user', 'department']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('department.name')
                ->label(__('resources/authority/strings.fields.department'))
                ->state(fn($record) => $record->department?->displayLabel() ?? '-'),
            ExportColumn::make('user.name')
                ->label(__('resources/authority/strings.fields.user')),
            ExportColumn::make('sub_duty')
                ->label(__('resources/authority/strings.fields.sub_duty'))
                ->formatStateUsing(fn($state) => $state ? 'بله' : 'خیر'),
            ExportColumn::make('duty')
                ->label(__('resources/authority/strings.fields.duty'))
                ->state(fn($record) => $record->details['duty'] ?? ''),
            ExportColumn::make('execution_procedure')
                ->label(__('resources/authority/strings.fields.execution_procedure'))
                ->state(fn($record) => ExecutionProcedure::tryFrom($record->details['execution_procedure'] ?? '')?->getLabel() ?? ''),
            ExportColumn::make('repeat_frequency')
                ->label(__('resources/authority/strings.fields.repeat_frequency'))
                ->state(fn($record) => RepeatFrequency::tryFrom($record->details['repeat_frequency'] ?? '')?->getLabel() ?? ''),
            ExportColumn::make('impact_score')
                ->label(__('resources/authority/strings.fields.impact_score'))
                ->state(fn($record) => ImpactScore::tryFrom($record->details['impact_score'] ?? '')?->getLabel() ?? ''),
            ExportColumn::make('proposed_delegation')
                ->label(__('resources/authority/strings.fields.proposed_delegation'))
                ->state(fn($record) => DelegationLevel::tryFrom($record->details['proposed_delegation'] ?? '')?->getLabel() ?? ''),
            ExportColumn::make('approved_delegation')
                ->label(__('resources/authority/strings.fields.approved_delegation'))
                ->state(fn($record) => DelegationLevel::tryFrom($record->details['approved_delegation'] ?? '')?->getLabel() ?? ''),
            ExportColumn::make('co_delegate')
                ->label(__('resources/authority/strings.fields.co_delegate'))
                ->state(fn($record) => $record->details['co_delegate'] ?? ''),
            ExportColumn::make('created_at')
                ->label(__('resources/authority/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/authority/strings.export.completed', ['count' => $count]);
    }
}
