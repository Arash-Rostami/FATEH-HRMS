<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FAQResource\Exports\FAQExporter;
use App\Filament\Resources\FAQResource\Pages\CreateFAQ;
use App\Filament\Resources\FAQResource\Pages\EditFAQ;
use App\Filament\Resources\FAQResource\Pages\ListFAQs;
use App\Filament\Resources\FAQResource\Schemas\FAQFormPresenter;
use App\Filament\Resources\FAQResource\Schemas\FAQInfolistPresenter;
use App\Filament\Resources\FAQResource\Schemas\FAQTablePresenter;
use App\Models\FAQ;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FAQResource extends Resource
{
    use FilamentActions, FilamentFilters;

    protected static ?string $model = FAQ::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/faq/strings.form.section_meta'))
                ->icon('heroicon-o-tag')
                ->description(__('resources/faq/strings.form.section_meta_description'))
                ->schema([
                    FAQFormPresenter::category(),
                    FAQFormPresenter::departmentId(),
                    FAQFormPresenter::userId(),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('resources/faq/strings.form.section_content'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->description(__('resources/faq/strings.form.section_content_description'))
                ->schema([
                    FAQFormPresenter::question(),
                    FAQFormPresenter::answer(),
                ])
                ->columns(1)
                ->columnSpanFull(),
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
            __('resources/faq/strings.fields.category') => $record->category,
            __('resources/faq/strings.fields.user') => $record->user?->name ?? '-',
            __('resources/faq/strings.fields.department') => $record->department?->name ?? '-',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return str(strip_tags($record->question))
            ->trim()
            ->limit(60);
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['department.description', 'category', 'department.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/faq/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/faq/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFAQs::route('/'),
            'create' => CreateFAQ::route('/create'),
            'edit' => EditFAQ::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/faq/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Section::make()
                        ->hiddenLabel()
                        ->schema([
                            FAQInfolistPresenter::question(),
                            FAQInfolistPresenter::answer(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    Section::make()
                        ->hiddenLabel()
                        ->schema([
                            FAQInfolistPresenter::id(),
                            FAQInfolistPresenter::category(),
                            FAQInfolistPresenter::user(),
                            FAQInfolistPresenter::department(),
                            FAQInfolistPresenter::createdAt(),
                            FAQInfolistPresenter::updatedAt(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                FAQTablePresenter::id(),
                FAQTablePresenter::category(),
                FAQTablePresenter::question(),
                FAQTablePresenter::user(),
                FAQTablePresenter::department(),
                FAQTablePresenter::answer(),
                FAQTablePresenter::createdAt(),
            ])
            ->groups([
                FAQTablePresenter::categoryGroup(),
                FAQTablePresenter::departmentGroup(),
                FAQTablePresenter::userGroup(),
            ])
            ->filters([
                FAQTablePresenter::categoryFilter(),
                FAQTablePresenter::departmentFilter(),
                FAQTablePresenter::userFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(FAQExporter::class))
            ->striped()
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('id', 'desc');
    }
}
