<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\CredentialResource\Schemas\CredentialFormPresenter;
use App\Filament\Resources\CredentialResource\Schemas\CredentialInfolistPresenter;
use App\Filament\Resources\CredentialResource\Schemas\CredentialTablePresenter;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class CredentialRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'credentials';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/credential/strings.form.section_main'))
                ->icon('heroicon-o-key')
                ->schema([
                    CredentialTablePresenter::id(),
                    CredentialFormPresenter::appName(),
                    CredentialFormPresenter::username(),
                    CredentialFormPresenter::password(),
                    CredentialFormPresenter::link(),
                    CredentialFormPresenter::note(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public static function getModelLabel(): string
    {
        return __('resources/credential/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/credential/strings.plural_label');
    }

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/credential/strings.plural_label');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/credential/strings.infolist.section_main'))
                ->icon('heroicon-o-key')
                ->schema([
                    CredentialInfolistPresenter::appName(),
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
            ->recordTitleAttribute('app_name')
            ->columns([
                CredentialTablePresenter::id(),
                CredentialTablePresenter::appName(),
                CredentialTablePresenter::username(),
                CredentialTablePresenter::passwordColumn(),
                CredentialTablePresenter::link(),
                CredentialTablePresenter::createdAt(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')
                    ->label('افزودن اطلاعات'),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc');
    }
}
