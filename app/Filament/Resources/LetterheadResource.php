<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LetterheadResource\Pages\{CreateLetterhead, EditLetterhead, ListLetterheads};
use App\Filament\Resources\LetterheadResource\Schemas\{LetterheadFormPresenter, LetterheadTablePresenter};
use App\Models\Letterhead;
use App\Traits\Filament\{AuthorizesByPermission, FilamentActions, FilamentAdminGuide, FilamentFilters, FilamentResourceSwitch, FilamentVisibility};
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LetterheadResource extends Resource
{
    use AuthorizesByPermission, FilamentActions, FilamentAdminGuide, FilamentFilters, FilamentResourceSwitch, FilamentVisibility;

    protected static ?string $model = Letterhead::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $slug = 'correspondence-letterheads';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static array $guide = [
        ['label' => 'ساخت و تنظیم سربرگ', 'icon' => 'description', 'view' => 'filament.resources.letterhead.guide.overview'],
    ];

    public static function shouldRegisterNavigation(): bool
    {
        return ! CorrespondenceResource::canViewAny() && ! tenantOffName(static::getSlug()) && static::canViewAny();
    }

    protected static function switchGroup(): array
    {
        return array_values(array_filter(
            [CorrespondenceResource::class, self::class],
            fn (string $resource) => ! tenantOffName($resource::getSlug())
        ));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('parent:id,name')
            ->withCount(['correspondences' => fn (Builder $query) => $query->withTrashed(), 'children']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/correspondence/strings.admin.letterhead.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/correspondence/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLetterheads::route('/'),
            'create' => CreateLetterhead::route('/create'),
            'edit' => EditLetterhead::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/correspondence/strings.admin.letterhead.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    LetterheadFormPresenter::name(),
                    LetterheadFormPresenter::kind(),
                    LetterheadFormPresenter::size(),
                    LetterheadFormPresenter::lang(),
                    LetterheadFormPresenter::divider(),
                    LetterheadFormPresenter::parentId(),
                    LetterheadFormPresenter::divider(),
                    LetterheadFormPresenter::pattern(),
                    LetterheadFormPresenter::patternPreview(),
                    LetterheadFormPresenter::prefix(),
                    LetterheadFormPresenter::startAt(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make()
                ->hiddenLabel()
                ->schema([
                    LetterheadFormPresenter::headerFrom(),
                    LetterheadFormPresenter::headerImage(),
                    LetterheadFormPresenter::footerFrom(),
                    LetterheadFormPresenter::footerImage(),
                    LetterheadFormPresenter::stampImage(),
                    LetterheadFormPresenter::watermark(),
                ])
                ->columns(2)
                ->visible(self::isHeadKind(...))
                ->dehydratedWhenHidden()
                ->columnSpanFull(),
            Section::make()
                ->hiddenLabel()
                ->schema([
                    LetterheadFormPresenter::marginsGroup(),
                    LetterheadFormPresenter::styleGroup(),
                    LetterheadFormPresenter::fieldsBoxGroup(),
                    LetterheadFormPresenter::configGroup(),
                    LetterheadFormPresenter::signLayout(),
                    LetterheadFormPresenter::senderBlock(),
                    LetterheadFormPresenter::pageNumbers(),
                    self::activeToggle(),
                ])
                ->columns(2)
                ->visible(self::isHeadKind(...))
                ->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                LetterheadTablePresenter::id(),
                LetterheadTablePresenter::name(),
                LetterheadTablePresenter::kind(),
                LetterheadTablePresenter::size(),
                self::activeColumn(),
                LetterheadTablePresenter::correspondencesCount(),
                LetterheadTablePresenter::childrenCount(),
                LetterheadTablePresenter::parent(),
                LetterheadTablePresenter::pattern(),
                LetterheadTablePresenter::startAt(),
                LetterheadTablePresenter::createdAt(),
            ])
            ->groups([
                LetterheadTablePresenter::kindGroup(),
                self::activeGroup(),
            ])
            ->filters([
                LetterheadTablePresenter::kindFilter(),
                self::activeFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([
                static::bulkDeleteAction(),
            ])
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('name')
            ->striped();
    }

    private static function isHeadKind(Get $get): bool
    {
        return ($get('kind') ?? Letterhead::KIND_HEAD) !== Letterhead::KIND_REGISTRY;
    }
}
