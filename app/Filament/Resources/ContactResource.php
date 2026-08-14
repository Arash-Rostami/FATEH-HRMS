<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Exports\ContactExporter;
use App\Filament\Resources\ContactResource\RelationManagers\RepliesRelationManager;
use App\Filament\Resources\ContactResource\Pages\{EditContact, ListContacts};
use App\Filament\Resources\ContactResource\Schemas\{ContactFormPresenter,
    ContactInfolistPresenter,
    ContactTablePresenter};
use App\Models\Message;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentAdminGuide;
use App\Traits\FilamentFilters;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ContactResource extends Resource
{
    use FilamentAdminGuide, FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Message::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 7;

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'menu_book', 'view' => 'filament.resources.contact.guide.overview'],
        ['label' => 'عملیات ادمین', 'icon' => 'admin_panel_settings', 'view' => 'filament.resources.contact.guide.admin-ops'],
        ['label' => 'زبانه‌های فهرست', 'icon' => 'tab', 'view' => 'filament.resources.contact.guide.list-tabs'],
        ['label' => 'حذف و هرس خودکار', 'icon' => 'delete_sweep', 'view' => 'filament.resources.contact.guide.prune'],
        ['label' => 'تجربهٔ کاربر', 'icon' => 'visibility', 'view' => 'filament.resources.contact.guide.user'],
    ];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record) {
            return null;
        }

        return Str::limit($record->body ?? '', 60);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/contact/strings.form.section_meta'))
                ->icon('heroicon-o-users')
                ->schema([
                    ContactFormPresenter::sender(),
                    ContactFormPresenter::recipient(),
                ])
                ->columns(2),

            Section::make(__('resources/contact/strings.form.section_content'))
                ->icon('heroicon-o-chat-bubble-left')
                ->schema([
                    ContactFormPresenter::replyToPreview(),
                    ContactFormPresenter::attachmentsPreview(),
                    ContactFormPresenter::body(),
                ])
                ->columns(1),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class,])
            ->with(['sender', 'recipient', 'replyTo.sender']);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/contact/strings.fields.sender') => $record->sender?->name ?? '—',
            __('resources/contact/strings.fields.recipient') => $record->recipient?->name ?? '—',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return mb_substr($record->body ?? '', 0, 80);
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['body'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/contact/strings.label');
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('resources/contact/strings.nav_badge_tooltip');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/contact/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/contact/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    ContactInfolistPresenter::sender(),
                    ContactInfolistPresenter::recipient(),
                    ContactInfolistPresenter::isEdited(),
                    ContactInfolistPresenter::createdAt(),
                    ContactInfolistPresenter::readAt(),

                    ContactInfolistPresenter::replyTo(),
                    ContactInfolistPresenter::body(),
                    ContactInfolistPresenter::attachments(),

                    ContactInfolistPresenter::updatedAt(),
                    ContactInfolistPresenter::deletedAt(),
                    ContactInfolistPresenter::prunableWarning(),
                ])
                ->columnSpanFull()
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ContactTablePresenter::id(),
                ContactTablePresenter::sender(),
                ContactTablePresenter::recipient(),
                ContactTablePresenter::body(),
                ContactTablePresenter::hasReply(),
                ContactTablePresenter::attachmentsCount(),
                ContactTablePresenter::isEdited(),
                ContactTablePresenter::createdAt(),
                ContactTablePresenter::readAt(),
                ContactTablePresenter::deletedAt(),
                ContactTablePresenter::prunableWarning(),
            ])
            ->groups([
                ContactTablePresenter::senderGroup(),
                ContactTablePresenter::recipientGroup(),
            ])
            ->filters([
                ContactTablePresenter::unreadFilter(),
                ContactTablePresenter::editedFilter(),
                ContactTablePresenter::hasReplyFilter(),
                ContactTablePresenter::hasAttachmentsFilter(),
                ContactTablePresenter::senderFilter(),
                ContactTablePresenter::recipientFilter(),
                self::createdAtFilter(),
                ContactTablePresenter::pruningSoonFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::restoreAction(),
                self::deleteAction()->visible(fn($record) => !$record->trashed()),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(ContactExporter::class))
            ->emptyStateIcon('heroicon-o-chat-bubble-left-ellipsis')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RepliesRelationManager::class,
        ];
    }
}
