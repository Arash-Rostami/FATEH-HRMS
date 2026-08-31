<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use App\Filament\Resources\ContactResource\Schemas\ContactInfolistPresenter;
use App\Filament\Resources\ContactResource\Schemas\ContactTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RepliesRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'replies';

    public static function getModelLabel(): string
    {
        return __('resources/contact/strings.fields.has_reply');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/contact/strings.fields.replies');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/contact/strings.fields.replies');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    ContactInfolistPresenter::sender(),
                    ContactInfolistPresenter::recipient(),
                    ContactInfolistPresenter::body(),
                    ContactInfolistPresenter::createdAt(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['sender', 'recipient']))
            ->columns([
                ContactTablePresenter::sender(),
                ContactTablePresenter::recipient(),
                ContactTablePresenter::body(),
                ContactTablePresenter::createdAt(),
                ContactTablePresenter::readAt(),
            ])
            ->recordActions([
                self::viewAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'asc');
    }
}
