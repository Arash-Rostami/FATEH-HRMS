<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CorrespondenceResource\Pages\{ListCorrespondences, ViewCorrespondence};
use App\Filament\Resources\CorrespondenceResource\Schemas\{CorrespondenceInfolistPresenter, CorrespondenceTablePresenter};
use App\Models\Correspondence;
use App\Traits\Filament\{AuthorizesByPermission, FilamentActions, FilamentAdminGuide, FilamentFilters, FilamentResourceSwitch};
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CorrespondenceResource extends Resource
{
    use AuthorizesByPermission, FilamentActions, FilamentAdminGuide, FilamentFilters, FilamentResourceSwitch;

    protected static ?string $model = Correspondence::class;
    protected static ?string $recordTitleAttribute = 'number';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static array $guide = [
        ['label' => 'بررسی نامه‌ها', 'icon' => 'mail', 'view' => 'filament.resources.correspondence.guide.overview'],
    ];

    protected static function switchGroup(): array
    {
        return array_values(array_filter(
            [self::class, LetterheadResource::class],
            fn (string $resource) => ! tenantOffName($resource::getSlug())
        ));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user:id,name');
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->select(Correspondence::ADMIN_COLUMNS);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'subject', 'user.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/correspondence/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/correspondence/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCorrespondences::route('/'),
            'view' => ViewCorrespondence::route('/{record}'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/correspondence/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->extraAttributes(['class' => 'fi-infolist-panel'])
                ->hiddenLabel()
                ->schema([
                    CorrespondenceInfolistPresenter::subject(),
                    CorrespondenceInfolistPresenter::owner(),
                    CorrespondenceInfolistPresenter::status(),
                    CorrespondenceInfolistPresenter::type(),
                    CorrespondenceInfolistPresenter::direction(),
                    CorrespondenceInfolistPresenter::classification(),
                    CorrespondenceInfolistPresenter::urgency(),
                    CorrespondenceInfolistPresenter::template(),
                    CorrespondenceInfolistPresenter::templateScope(),
                    CorrespondenceInfolistPresenter::mode(),
                    CorrespondenceInfolistPresenter::routeMode(),
                    CorrespondenceInfolistPresenter::signMode(),
                    CorrespondenceInfolistPresenter::senderCategory(),
                    CorrespondenceInfolistPresenter::letterhead(),
                    CorrespondenceInfolistPresenter::number(),
                    CorrespondenceInfolistPresenter::awaited(),
                    CorrespondenceInfolistPresenter::deadline(),
                    CorrespondenceInfolistPresenter::deliveryTag(),
                    CorrespondenceInfolistPresenter::createdAt(),
                ])
                ->columnSpanFull()
                ->columns(4),
            Section::make()
                ->hiddenLabel()
                ->schema([
                    CorrespondenceInfolistPresenter::parties(),
                    CorrespondenceInfolistPresenter::events(),
                    CorrespondenceInfolistPresenter::versions(),
                ])
                ->columnSpanFull()
                ->columns(1),
            Section::make()
                ->hiddenLabel()
                ->schema([
                    CorrespondenceInfolistPresenter::preview(),
                ])
                ->visible(fn (Correspondence $record): bool => CorrespondenceInfolistPresenter::isNormal($record))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->select(Correspondence::ADMIN_COLUMNS))
            ->columns([
                CorrespondenceTablePresenter::id(),
                CorrespondenceTablePresenter::number(),
                CorrespondenceTablePresenter::subject(),
                CorrespondenceTablePresenter::type(),
                CorrespondenceTablePresenter::direction(),
                CorrespondenceTablePresenter::classification(),
                CorrespondenceTablePresenter::urgency(),
                CorrespondenceTablePresenter::status(),
                CorrespondenceTablePresenter::routeMode(),
                CorrespondenceTablePresenter::signMode(),
                CorrespondenceTablePresenter::senderCategory(),
                CorrespondenceTablePresenter::template(),
                CorrespondenceTablePresenter::author(),
                CorrespondenceTablePresenter::sent(),
                CorrespondenceTablePresenter::approved(),
                CorrespondenceTablePresenter::awaited(),
            ])
            ->groups([
                CorrespondenceTablePresenter::statusGroup(),
                CorrespondenceTablePresenter::authorGroup(),
                CorrespondenceTablePresenter::directionGroup(),
            ])
            ->filters([
                CorrespondenceTablePresenter::statusFilter(),
                CorrespondenceTablePresenter::typeFilter(),
                CorrespondenceTablePresenter::directionFilter(),
                CorrespondenceTablePresenter::templateFilter(),
                CorrespondenceTablePresenter::staleDraftFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
