<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotifyRuleResource\Pages\CreateNotifyRule;
use App\Filament\Resources\NotifyRuleResource\Pages\EditNotifyRule;
use App\Filament\Resources\NotifyRuleResource\Pages\ListNotifyLogs;
use App\Filament\Resources\NotifyRuleResource\Pages\ListNotifyRules;
use App\Filament\Resources\NotifyRuleResource\RelationManagers\LogsRelationManager;
use App\Filament\Resources\NotifyRuleResource\Schemas\Action\SendTestAction;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyRuleFormPresenter;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyRuleInfolistPresenter;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyRuleTablePresenter;
use App\Models\NotifyRule;
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
use Illuminate\Database\Eloquent\Model;

class NotifyRuleResource extends Resource
{
    use AuthorizesByPermission, FilamentActions, FilamentAdminGuide, FilamentFilters;

    protected static ?string $model = NotifyRule::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-at-symbol';

    protected static ?int $navigationSort = 10;

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'menu_book', 'view' => 'filament.resources.notify_rule.guide.overview'],
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/notify_rule/strings.form.section_main'))
                ->icon('heroicon-o-at-symbol')
                ->description(__('resources/notify_rule/strings.form.section_main_description'))
                ->schema([
                    NotifyRuleFormPresenter::name(),
                    NotifyRuleFormPresenter::model(),
                    NotifyRuleFormPresenter::events(),
                    NotifyRuleFormPresenter::channels(),
                    NotifyRuleFormPresenter::watch(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('resources/notify_rule/strings.form.section_conditions'))
                ->icon('heroicon-o-funnel')
                ->description(__('resources/notify_rule/strings.form.section_conditions_description'))
                ->schema([
                    NotifyRuleFormPresenter::conditionsMatch(),
                    NotifyRuleFormPresenter::conditions(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Section::make(__('resources/notify_rule/strings.form.section_recipients'))
                ->icon('heroicon-o-users')
                ->description(__('resources/notify_rule/strings.form.section_recipients_description'))
                ->schema(NotifyRuleFormPresenter::recipientFields())
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('resources/notify_rule/strings.form.section_message'))
                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->description(__('resources/notify_rule/strings.form.section_message_description'))
                ->schema(NotifyRuleFormPresenter::messageFields())
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('resources/notify_rule/strings.form.section_window'))
                ->icon('heroicon-o-clock')
                ->description(__('resources/notify_rule/strings.form.section_window_description'))
                ->schema([
                    NotifyRuleFormPresenter::activeFromDate(),
                    NotifyRuleFormPresenter::activeFromTime(),
                    NotifyRuleFormPresenter::activeUntilDate(),
                    NotifyRuleFormPresenter::activeUntilTime(),
                    NotifyRuleFormPresenter::isActive(),
                ])
                ->columns(5)
                ->columnSpanFull(),
        ]);
    }

    public static function canEdit(Model $record): bool
    {
        return ! $record->isOneOff() && static::permits('update');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('logs');
    }

    public static function getModelLabel(): string
    {
        return __('resources/notify_rule/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/notify_rule/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotifyRules::route('/'),
            'create' => CreateNotifyRule::route('/create'),
            'logs' => ListNotifyLogs::route('/logs'),
            'edit' => EditNotifyRule::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/notify_rule/strings.plural_label');
    }

    public static function getRelations(): array
    {
        return [LogsRelationManager::class];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->extraAttributes(['class' => 'fi-infolist-panel'])
                ->hiddenLabel()
                ->schema([
                    NotifyRuleInfolistPresenter::id(),
                    NotifyRuleInfolistPresenter::name(),
                    NotifyRuleInfolistPresenter::model(),
                    NotifyRuleInfolistPresenter::events(),
                    NotifyRuleInfolistPresenter::channels(),
                    NotifyRuleInfolistPresenter::recipients(),
                    NotifyRuleInfolistPresenter::extraContacts(),
                    NotifyRuleInfolistPresenter::conditions(),

                    NotifyRuleInfolistPresenter::divider(),
                    NotifyRuleInfolistPresenter::subject(),
                    NotifyRuleInfolistPresenter::lines(),

                    NotifyRuleInfolistPresenter::divider(),
                    NotifyRuleInfolistPresenter::isActive(),
                    NotifyRuleInfolistPresenter::activeFrom(),
                    NotifyRuleInfolistPresenter::activeUntil(),
                    NotifyRuleInfolistPresenter::createdAt(),
                    NotifyRuleInfolistPresenter::logsCount(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                NotifyRuleTablePresenter::id(),
                NotifyRuleTablePresenter::name(),
                NotifyRuleTablePresenter::model(),
                NotifyRuleTablePresenter::events(),
                NotifyRuleTablePresenter::conditions(),
                NotifyRuleTablePresenter::channels(),
                NotifyRuleTablePresenter::recipients(),
                NotifyRuleTablePresenter::isActive(),
                NotifyRuleTablePresenter::window(),
                NotifyRuleTablePresenter::logsCount(),
                NotifyRuleTablePresenter::createdAt(),
            ])
            ->filters([
                NotifyRuleTablePresenter::modelFilter(),
                NotifyRuleTablePresenter::isActiveFilter(),
                NotifyRuleTablePresenter::liveFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                SendTestAction::make('mail')->iconButton()->tooltip(__('resources/notify_rule/strings.actions.send_test_mail')),
                SendTestAction::make('sms')->iconButton()->tooltip(__('resources/notify_rule/strings.actions.send_test_sms')),
                self::editAction()->hidden(fn (NotifyRule $record) => $record->isOneOff()),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([self::bulkDeleteAction()])
            ->striped()
            ->emptyStateIcon('heroicon-o-at-symbol')
            ->emptyStateActions(self::guideEmptyStateActions())
            ->defaultSort('id', 'desc');
    }
}
