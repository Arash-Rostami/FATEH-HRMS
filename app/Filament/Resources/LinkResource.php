<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LinkResource\Exports\LinkExporter;
use App\Filament\Resources\LinkResource\Pages\{CreateLink, EditLink, ListLinks};
use App\Filament\Resources\LinkResource\Schemas\{LinkFormPresenter, LinkInfolistPresenter, LinkTablePresenter};
use App\Models\Link;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LinkResource extends Resource
{
    use FilamentActions, AuthorizesByPermission;

    protected static ?string $model = Link::class;
    protected static ?string $recordTitleAttribute = 'url_title';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-arrow-top-right-on-square';
    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Section::make(__('resources/link/strings.form.section_routing'))
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->schema([
                        Grid::make(2)->schema([
                            LinkFormPresenter::linkType(),
                            LinkFormPresenter::sequence(),
                        ]),
                        LinkFormPresenter::divider(),
                        LinkFormPresenter::url(),
                        LinkFormPresenter::internalUrl(),
                        LinkFormPresenter::companyIps(),
                    ])
                    ->columns(1)
                    ->columnSpan(1),

                Grid::make(1)->schema([
                    Section::make(__('resources/link/strings.form.section_info'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            LinkFormPresenter::urlTitle(),
                            LinkFormPresenter::urlDescription(),
                        ])
                        ->columns(1),

                    Section::make(__('resources/link/strings.form.section_media'))
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Grid::make(2)->schema([
                                LinkFormPresenter::image(),
                                LinkFormPresenter::icon(),
                            ]),
                            Grid::make(2)->schema([
                                LinkFormPresenter::imageDescription(),
                                LinkFormPresenter::iconDescription(),
                            ]),
                        ])
                        ->columns(1),
                ])
                    ->columnSpan(1),
            ])->columnSpanFull()
        ]);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/link/strings.fields.url') => $record->url,
            __('resources/link/strings.fields.link_type') => $record->link,
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->url_title;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['url_title', 'url', 'url_description'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/link/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/link/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLinks::route('/'),
            'create' => CreateLink::route('/create'),
            'edit' => EditLink::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/link/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    LinkInfolistPresenter::urlTitle(),
                    LinkInfolistPresenter::urlDescription(),
                    LinkInfolistPresenter::linkType(),
                    LinkInfolistPresenter::sequence(),

                    LinkInfolistPresenter::url(),
                    LinkInfolistPresenter::internalUrl(),
                    LinkInfolistPresenter::companyIps(),

                    LinkInfolistPresenter::icon(),
                    LinkInfolistPresenter::iconDescription(),
                    LinkInfolistPresenter::image(),
                    LinkInfolistPresenter::imageDescription(),

                    LinkInfolistPresenter::createdAt(),
                    LinkInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                LinkTablePresenter::sequence(),
                LinkTablePresenter::icon(),
                LinkTablePresenter::image(),
                LinkTablePresenter::urlTitle(),
                LinkTablePresenter::url(),
                LinkTablePresenter::internalUrl(),
                LinkTablePresenter::linkType(),
                LinkTablePresenter::hasSmartRouting(),
            ])
            ->reorderable('sequence')
            ->groups([
                LinkTablePresenter::typeGroup(),
            ])
            ->filters([
                LinkTablePresenter::linkTypeFilter(),
                LinkTablePresenter::smartRoutingFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(LinkExporter::class))
            ->emptyStateIcon('heroicon-o-link')
            ->defaultSort('sequence', 'asc')
            ->striped();
    }
}
