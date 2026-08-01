<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthorityResource\Exports\AuthorityExporter;
use App\Filament\Resources\AuthorityResource\Pages\{CreateAuthority, EditAuthority, ListAuthorities};
use App\Filament\Resources\AuthorityResource\Schemas\{AuthorityFormPresenter,
    AuthorityInfolistPresenter,
    AuthorityTablePresenter};
use App\Models\Authority;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuthorityResource extends Resource
{
    use FilamentActions, AuthorizesByPermission;

    protected static ?string $model = Authority::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 4;

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record) {
            return null;
        }

        return $record->duty ?? $record->department?->name ?? ('#' . $record->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/authority/strings.form.section_general'))
                ->icon('heroicon-o-information-circle')
                ->schema([
                    AuthorityFormPresenter::departmentId(),
                    AuthorityFormPresenter::userId(),
                    AuthorityFormPresenter::subDuty(),
                    AuthorityFormPresenter::divider(),
                    AuthorityFormPresenter::duty(),
                ])
                ->columns(2),

            Section::make(__('resources/authority/strings.form.section_details'))
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    AuthorityFormPresenter::executionProcedure(),
                    AuthorityFormPresenter::repeatFrequency(),
                    AuthorityFormPresenter::divider(),
                    AuthorityFormPresenter::impactScore(),
                    AuthorityFormPresenter::proposedDelegation(),
                    AuthorityFormPresenter::approvedDelegation(),
                    AuthorityFormPresenter::coDelegate(),
                ])
                ->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'department']);
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/authority/strings.fields.department') => $record->department?->displayLabel() ?? '—',
            __('resources/authority/strings.fields.user') => $record->user?->name ?? '—',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return strip_tags($record->details['duty'] ?? '—');
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['department.name', 'user.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/authority/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/authority/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuthorities::route('/'),
            'create' => CreateAuthority::route('/create'),
            'edit' => EditAuthority::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/authority/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/authority/strings.infolist.section_general'))
                ->icon('heroicon-o-information-circle')
                ->schema([
                    AuthorityInfolistPresenter::duty(),
                    AuthorityInfolistPresenter::department(),
                    AuthorityInfolistPresenter::user(),
                    AuthorityInfolistPresenter::subDuty(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('resources/authority/strings.infolist.section_details'))
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    AuthorityInfolistPresenter::executionProcedure(),
                    AuthorityInfolistPresenter::repeatFrequency(),
                    AuthorityInfolistPresenter::impactScore(),
                    AuthorityInfolistPresenter::proposedDelegation(),
                    AuthorityInfolistPresenter::approvedDelegation(),
                    AuthorityInfolistPresenter::coDelegate(),
                    AuthorityInfolistPresenter::createdAt(),
                    AuthorityInfolistPresenter::updatedAt(),
                ])
                ->columns(3)
                ->columnSpanFull(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                AuthorityTablePresenter::id(),
                AuthorityTablePresenter::duty(),
                AuthorityTablePresenter::department(),
                AuthorityTablePresenter::user(),
                AuthorityTablePresenter::approvedDelegation(),
                AuthorityTablePresenter::impactScore(),
                AuthorityTablePresenter::subDuty(),
                AuthorityTablePresenter::createdAt(),
            ])
            ->groups([
                AuthorityTablePresenter::departmentGroup(),
                AuthorityTablePresenter::delegationGroup(),
                AuthorityTablePresenter::impactScoreGroup(),
                AuthorityTablePresenter::executionProcedureGroup(),
                AuthorityTablePresenter::repeatFrequencyGroup(),
            ])
            ->filters([
                AuthorityTablePresenter::departmentFilter(),
                AuthorityTablePresenter::approvedDelegationFilter(),
                AuthorityTablePresenter::impactScoreFilter(),
                AuthorityTablePresenter::executionProcedureFilter(),
                AuthorityTablePresenter::repeatFrequencyFilter(),
                AuthorityTablePresenter::subDutyFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(AuthorityExporter::class))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
