<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RepliesRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'replies';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getModelLabel(): string
    {
        return __('resources/contact/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/contact/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/contact/strings.plural_label');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->searchable(false)
            ->headerActions([])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
