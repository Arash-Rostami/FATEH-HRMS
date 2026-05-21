<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\CredentialResource\Schemas\CredentialFormPresenter;
use App\Filament\Resources\CredentialResource\Schemas\CredentialInfolistPresenter;
use App\Filament\Resources\CredentialResource\Schemas\CredentialTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class CredentialsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'credentials';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Credential/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    CredentialFormPresenter::appName(),
                    CredentialFormPresenter::link(),
                    CredentialFormPresenter::note(),
                    CredentialFormPresenter::password(),
                    CredentialFormPresenter::userId(),
                    CredentialFormPresenter::username(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Credential/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Credential/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Credential/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Credential/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    CredentialInfolistPresenter::appName(),
                    CredentialInfolistPresenter::owner(),
                    CredentialInfolistPresenter::username(),
                    CredentialInfolistPresenter::password(),
                    CredentialInfolistPresenter::link(),
                    CredentialInfolistPresenter::note(),
                    CredentialInfolistPresenter::createdAt(),
                    CredentialInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                CredentialTablePresenter::appName(),
                CredentialTablePresenter::createdAt(),
                CredentialTablePresenter::id(),
                CredentialTablePresenter::link(),
                CredentialTablePresenter::owner(),
                CredentialTablePresenter::passwordColumn(),
                CredentialTablePresenter::username(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Credential/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
