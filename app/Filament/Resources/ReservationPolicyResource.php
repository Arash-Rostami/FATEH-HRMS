<?php

namespace App\Filament\Resources;

use App\Enums\ResourceType;
use App\Filament\Resources\ReservationPolicyResource\Pages\EditPolicy;
use App\Filament\Resources\ReservationPolicyResource\Pages\ListPolicies;
use App\Filament\Resources\ReservationPolicyResource\Schemas\PolicyFormPresenter;
use App\Filament\Resources\ReservationPolicyResource\Schemas\PolicyTablePresenter;
use App\Models\ReservationPolicy;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReservationPolicyResource extends Resource
{
    use FilamentActions, AuthorizesByPermission;

    protected static ?string $model = ReservationPolicy::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record) {
            return null;
        }

        return ResourceType::tryFrom($record->resource_type)?->getLabel() ?? $record->resource_type;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/policy/strings.form.section_time'))
                ->icon('heroicon-o-clock')
                ->schema([
                    PolicyFormPresenter::windowDays(),
                    PolicyFormPresenter::windowHours(),
                    PolicyFormPresenter::minDurationMinutes(),
                    PolicyFormPresenter::maxDurationMinutes(),
                    PolicyFormPresenter::allowedHoursStart(),
                    PolicyFormPresenter::allowedHoursEnd(),
                    PolicyFormPresenter::allowedDays(),
                ])
                ->columns(2),
            Section::make(__('resources/policy/strings.form.section_permissions'))
                ->icon('heroicon-o-cog-6-tooth')
                ->schema([
                    PolicyFormPresenter::maxPerUser(),
                    PolicyFormPresenter::maxCancelCount(),

                    PolicyFormPresenter::divider(),
                    PolicyFormPresenter::allowFullDay(),
                    PolicyFormPresenter::allowRepeat(),
                    PolicyFormPresenter::allowPartialCancel(),
                    PolicyFormPresenter::allowOverlapRelease(),
                    PolicyFormPresenter::requiresApproval(),
                    Section::make(__('resources/policy/strings.form.section_guide'))
                        ->icon('heroicon-o-information-circle')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            PolicyFormPresenter::errorLegend(),
                        ])->columnSpanFull(),
                ])
                ->columns(2),


        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return ReservationPolicy::groupedByResourceType();
    }

    public static function getModelLabel(): string
    {
        return __('resources/policy/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/policy/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPolicies::route('/'),
            'edit' => EditPolicy::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/policy/strings.plural_label');
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'resource_type';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                PolicyTablePresenter::resourceType(),
                PolicyTablePresenter::rulesCount(),
                PolicyTablePresenter::lastUpdated(),
            ])
            ->recordUrl(fn(ReservationPolicy $record): string => static::getUrl('edit', ['record' => $record->resource_type]))
            ->recordActions([
                PolicyTablePresenter::editAction(static::class),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('resource_type')
            ->paginated(false);
    }
}
