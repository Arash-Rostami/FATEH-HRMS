<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Exports\EventExporter;
use App\Traits\AuthorizesByPermission;
use App\Filament\Resources\EventResource\Pages\{CreateEvent, EditEvent, ListEvents};
use App\Filament\Resources\EventResource\Schemas\{EventFormPresenter, EventInfolistPresenter, EventTablePresenter};
use App\Models\Event;
use App\Traits\FilamentActions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EventResource extends Resource
{
    use FilamentActions, AuthorizesByPermission;

    protected static ?string $model = Event::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/event/strings.form.section_info'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    EventFormPresenter::title(),
                    EventFormPresenter::description(),
                ])
                ->columns(1),

            Group::make()
                ->schema([
                    Section::make(__('resources/event/strings.form.section_schedule'))
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            EventFormPresenter::dateJalali(),
                            EventFormPresenter::dateTimePart(),
                        ])->columns(3),

                    Section::make(__('resources/event/strings.form.section_access'))
                        ->icon('heroicon-o-lock-closed')
                        ->schema([
                            EventFormPresenter::private(),
                            EventFormPresenter::userId(),
                        ])->columns(2),
                ])

        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
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
            __('resources/event/strings.fields.user') => $record->user?->name ?? '—',
            __('resources/event/strings.fields.date') => $record->date?->format('Y-m-d') ?? '—',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'user.name'];
    }

    public static function getModelLabel(): string
    {
        return 'تقویم';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/event/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return 'تقویم';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    EventInfolistPresenter::date(),
                    EventInfolistPresenter::user(),
                    EventInfolistPresenter::private(),
                    EventInfolistPresenter::title(),
                    EventInfolistPresenter::description(),
                    EventInfolistPresenter::createdAt(),
                    EventInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                EventTablePresenter::id(),
                EventTablePresenter::title(),
                EventTablePresenter::user(),
                EventTablePresenter::date(),
                EventTablePresenter::private(),
                EventTablePresenter::createdAt(),
            ])
            ->groups([
                EventTablePresenter::visibilityGroup(),
            ])
            ->filters([
                EventTablePresenter::userFilter(),
                EventTablePresenter::visibilityFilter(),
                EventTablePresenter::upcomingFilter(),
                EventTablePresenter::dateRangeFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(EventExporter::class))
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->defaultSort('date', 'desc')
            ->striped();
    }
}
