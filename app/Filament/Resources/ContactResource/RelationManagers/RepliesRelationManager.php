<?php
namespace App\Filament\Resources\ContactResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\ContactResource\Schemas\ContactFormPresenter;
use App\Filament\Resources\ContactResource\Schemas\ContactInfolistPresenter;
use App\Filament\Resources\ContactResource\Schemas\ContactTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class RepliesRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'replies';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Message/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    ContactFormPresenter::body(),
                    ContactFormPresenter::recipient(),
                    ContactFormPresenter::replyToPreview(),
                    ContactFormPresenter::sender(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Message/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Message/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Message/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Message/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    ContactInfolistPresenter::body(),
                    ContactInfolistPresenter::createdAt(),
                    ContactInfolistPresenter::deletedAt(),
                    ContactInfolistPresenter::isEdited(),
                    ContactInfolistPresenter::prunableWarning(),
                    ContactInfolistPresenter::readAt(),
                    ContactInfolistPresenter::recipient(),
                    ContactInfolistPresenter::replyTo(),
                    ContactInfolistPresenter::sender(),
                    ContactInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ContactTablePresenter::body(),
                ContactTablePresenter::createdAt(),
                ContactTablePresenter::deletedAt(),
                ContactTablePresenter::hasReply(),
                ContactTablePresenter::id(),
                ContactTablePresenter::isEdited(),
                ContactTablePresenter::prunableWarning(),
                ContactTablePresenter::readAt(),
                ContactTablePresenter::recipient(),
                ContactTablePresenter::sender(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Message/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
