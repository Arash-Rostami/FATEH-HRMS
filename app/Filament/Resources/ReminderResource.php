<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReminderResource\Pages\CreateReminder;
use App\Filament\Resources\ReminderResource\Pages\EditReminder;
use App\Filament\Resources\ReminderResource\Pages\ListReminders;
use App\Filament\Resources\ReminderResource\Schemas\ReminderFormPresenter;
use App\Filament\Resources\ReminderResource\Schemas\ReminderInfolistPresenter;
use App\Filament\Resources\ReminderResource\Schemas\ReminderTablePresenter;
use App\Models\Reminder;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentAdminGuide;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReminderResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission, FilamentAdminGuide;

    protected static ?string $model = Reminder::class;
    protected static ?string $recordTitleAttribute = 'title';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?int $navigationSort = 9;

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'menu_book', 'view' => 'filament.resources.reminder.guide.overview'],
        ['label' => 'تجربهٔ کاربر', 'icon' => 'visibility', 'view' => 'filament.resources.reminder.guide.user'],
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/reminder/strings.form.section_main'))
                ->icon('heroicon-o-bell-alert')
                ->description(__('resources/reminder/strings.form.section_main_description'))
                ->schema([
                    ReminderFormPresenter::userId(),
                    ReminderFormPresenter::recurs(),
                    ReminderFormPresenter::title(),
                    ReminderFormPresenter::dueDate(),
                    ReminderFormPresenter::dueTime(),
                    ReminderFormPresenter::notes(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user:id,name']);
    }

    public static function getModelLabel(): string
    {
        return __('resources/reminder/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/reminder/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReminders::route('/'),
            'create' => CreateReminder::route('/create'),
            'edit' => EditReminder::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/reminder/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    ReminderInfolistPresenter::id(),
                    ReminderInfolistPresenter::user(),
                    ReminderInfolistPresenter::title(),
                    ReminderInfolistPresenter::notes(),
                    ReminderInfolistPresenter::dueAt(),
                    ReminderInfolistPresenter::recurs(),
                    ReminderInfolistPresenter::completedAt(),
                    ReminderInfolistPresenter::snoozedUntil(),
                    ReminderInfolistPresenter::createdAt(),
                ])
                ->columnSpanFull()
                ->columns(2),

            Section::make(__('resources/reminder/strings.infolist.section_notify'))
                ->icon('heroicon-o-bell-alert')
                ->schema([
                    ReminderInfolistPresenter::channels(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ReminderTablePresenter::id(),
                ReminderTablePresenter::user(),
                ReminderTablePresenter::title(),
                ReminderTablePresenter::dueAt(),
                ReminderTablePresenter::recurs(),
                ReminderTablePresenter::completed(),
                ReminderTablePresenter::createdAt(),
            ])
            ->filters([
                ReminderTablePresenter::userFilter(),
                ReminderTablePresenter::recursFilter(),
                ReminderTablePresenter::completedFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([self::bulkDeleteAction()])
            ->striped()
            ->emptyStateIcon('heroicon-o-bell-alert')
            ->emptyStateActions(self::guideEmptyStateActions())
            ->defaultSort('due_at', 'desc');
    }
}
