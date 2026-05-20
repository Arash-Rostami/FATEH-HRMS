<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class ReservationsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'reservations';
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Reservation/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Reservation/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Reservation/strings.plural_label');
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
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Reservation/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
