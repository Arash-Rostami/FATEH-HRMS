<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;

class PhotosRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'photos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getModelLabel(): string
    {
        return __('resources/Photo/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/Photo/strings.plural_label');
    }

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Photo/strings.plural_label');
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
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Photo/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
