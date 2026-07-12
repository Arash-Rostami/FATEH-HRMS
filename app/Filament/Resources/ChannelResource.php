<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChannelResource\Exports\ChannelExporter;
use App\Filament\Resources\ChannelResource\Pages\{CreateChannel, EditChannel, ListChannels};
use App\Filament\Resources\ChannelResource\RelationManagers\{ChannelMembersRelationManager, ChannelMessagesRelationManager};
use App\Filament\Resources\ChannelResource\Schemas\{ChannelFormPresenter, ChannelInfolistPresenter, ChannelTablePresenter};
use App\Models\Channel;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChannelResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Channel::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/channel/strings.form.section_meta'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->schema([
                    ChannelFormPresenter::name(),
                    ChannelFormPresenter::slug(),
                    ChannelFormPresenter::type(),
                    ChannelFormPresenter::owner(),
                ])
                ->columns(2),

            Section::make(__('resources/channel/strings.form.section_content'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    ChannelFormPresenter::description(),
                ])
                ->columns(1),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['owner'])
            ->withCount(['members', 'messages']);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/channel/strings.fields.type') => $record->type?->getLabel() ?? '—',
            __('resources/channel/strings.fields.owner') => $record->owner?->name ?? '—',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name ?? '';
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/channel/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/channel/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChannels::route('/'),
            'create' => CreateChannel::route('/create'),
            'edit' => EditChannel::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/channel/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    ChannelInfolistPresenter::name(),
                    ChannelInfolistPresenter::slug(),
                    ChannelInfolistPresenter::type(),
                    ChannelInfolistPresenter::owner(),
                    ChannelInfolistPresenter::membersCount(),
                    ChannelInfolistPresenter::messagesCount(),
                    ChannelInfolistPresenter::description(),
                    ChannelInfolistPresenter::createdAt(),
                    ChannelInfolistPresenter::updatedAt(),
                    ChannelInfolistPresenter::deletedAt(),
                    ChannelInfolistPresenter::prunableWarning(),
                ])
                ->columnSpanFull()
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ChannelTablePresenter::id(),
                ChannelTablePresenter::name(),
                ChannelTablePresenter::slug(),
                ChannelTablePresenter::type(),
                ChannelTablePresenter::membersCount(),
                ChannelTablePresenter::messagesCount(),
                ChannelTablePresenter::createdAt(),
                ChannelTablePresenter::deletedAt(),
                ChannelTablePresenter::prunableWarning(),
            ])
            ->groups([
                ChannelTablePresenter::typeGroup(),
            ])
            ->filters([
                ChannelTablePresenter::typeFilter(),
                ChannelTablePresenter::pruningSoonFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::restoreAction(),
                self::deleteAction()->visible(fn($record) => !$record->trashed()),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(ChannelExporter::class))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            ChannelMessagesRelationManager::class,
            ChannelMembersRelationManager::class,
        ];
    }
}