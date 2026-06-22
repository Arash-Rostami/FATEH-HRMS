<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdResource\Enums\AdGender;
use App\Filament\Resources\AdResource\Enums\AdStatus;
use App\Filament\Resources\AdResource\Exports\AdExporter;
use App\Filament\Resources\AdResource\Pages\CreateAd;
use App\Filament\Resources\AdResource\Pages\EditAd;
use App\Filament\Resources\AdResource\Pages\ListAds;
use App\Filament\Resources\AdResource\Schemas\AdFormPresenter;
use App\Filament\Resources\AdResource\Schemas\AdInfolistPresenter;
use App\Filament\Resources\AdResource\Schemas\AdTablePresenter;
use App\Models\Ad;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Ad::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-briefcase';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/ad/strings.form.section_main'))
                ->icon('heroicon-o-megaphone')
                ->description(__('resources/ad/strings.form.section_main_description'))
                ->schema([
                    AdFormPresenter::position(),
                    AdFormPresenter::gender(),
                    AdFormPresenter::divider(),
                    AdFormPresenter::link(),
                    AdFormPresenter::active(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('resources/ad/strings.form.section_requirements'))
                ->icon('heroicon-o-clipboard-document-list')
                ->description(__('resources/ad/strings.form.section_requirements_description'))
                ->schema([
                    AdFormPresenter::certificate(),
                    AdFormPresenter::skill(),
                    AdFormPresenter::experience(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Section::make(__('resources/ad/strings.form.section_extra'))
                ->icon('heroicon-o-document-text')
                ->description(__('resources/ad/strings.form.section_extra_description'))
                ->schema([
                    AdFormPresenter::extra(),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ]);
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil')
                ->url(static::getUrl('edit', ['record' => $record])),
        ]
            ->validationMessages([
                'url' => __('resources/ad/strings.validation.edit.url')
            ]);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/ad/strings.fields.gender') => AdGender::from($record->gender)->getLabel(),
            __('resources/ad/strings.fields.active') => AdStatus::fromBool($record->active)->getLabel(),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->position ?? __('resources/ad/strings.untitled');
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['position', 'skill', 'certificate'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/ad/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/ad/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAds::route('/'),
            'create' => CreateAd::route('/create'),
            'edit' => EditAd::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/ad/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/ad/strings.infolist.tab_main'))
                        ->icon('heroicon-o-megaphone')
                        ->schema([
                            Section::make(__('resources/ad/strings.infolist.section_main'))
                                ->schema([
                                    AdInfolistPresenter::id(),
                                    AdInfolistPresenter::position(),
                                    AdInfolistPresenter::gender(),
                                    AdInfolistPresenter::active(),
                                    AdInfolistPresenter::link(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    Tab::make(__('resources/ad/strings.infolist.tab_requirements'))
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([
                            Section::make()
                                ->schema([
                                    AdInfolistPresenter::certificate(),
                                    AdInfolistPresenter::skill(),
                                    AdInfolistPresenter::experience(),
                                ])
                                ->columns(1)
                                ->columnSpanFull(),

                            Section::make()
                                ->schema([

                                    AdInfolistPresenter::createdAt(),
                                    AdInfolistPresenter::updatedAt(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    Tab::make(__('resources/ad/strings.infolist.tab_extra'))
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make()
                                ->schema([
                                    AdInfolistPresenter::extra(),
                                ])
                                ->columns(1)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                AdTablePresenter::id(),
                AdTablePresenter::position(),
                AdTablePresenter::gender(),
                AdTablePresenter::active(),
                AdTablePresenter::link(),
                AdTablePresenter::certificate(),
                AdTablePresenter::skill(),
                AdTablePresenter::experience(),
                AdTablePresenter::createdAt(),
            ])
            ->groups([
                AdTablePresenter::genderGroup(),
                AdTablePresenter::activeGroup(),
            ])
            ->filters([
                AdTablePresenter::activeFilter(),
                AdTablePresenter::genderFilter(),
                AdTablePresenter::hasExperienceFilter(),
                AdTablePresenter::hasCertificateFilter(),
                AdTablePresenter::hasSkillFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(AdExporter::class))
            ->striped()
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('id', 'desc');
    }
}
