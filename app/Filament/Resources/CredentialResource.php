<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CredentialResource\Exports\CredentialExporter;
use App\Filament\Resources\CredentialResource\Pages\CreateCredential;
use App\Filament\Resources\CredentialResource\Pages\EditCredential;
use App\Filament\Resources\CredentialResource\Pages\ListCredentials;
use App\Filament\Resources\CredentialResource\Schemas\CredentialFormPresenter;
use App\Filament\Resources\CredentialResource\Schemas\CredentialInfolistPresenter;
use App\Filament\Resources\CredentialResource\Schemas\CredentialTablePresenter;
use App\Models\Credential;
use App\Traits\FilamentActions;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CredentialResource extends Resource
{
    use FilamentActions;

    protected static ?string $model = Credential::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-key';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/credential/strings.form.section_main'))
                ->icon('heroicon-o-key')
                ->description(__('resources/credential/strings.form.section_description'))
                ->schema([
                    CredentialFormPresenter::userId(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user'])
            ->when(
                !auth()->user()?->isAdmin(),
                fn(Builder $q) => $q->where('user_id', auth()->id())
            );
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/credential/strings.fields.username') => $record->username,
            __('resources/credential/strings.fields.user') => $record->user?->name,
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->app_name;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['app_name', 'username', 'user.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/credential/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/credential/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCredentials::route('/'),
            'create' => CreateCredential::route('/create'),
            'edit' => EditCredential::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/credential/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/credential/strings.infolist.section_main'))
                ->icon('heroicon-o-key')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                CredentialTablePresenter::id(),
                CredentialTablePresenter::appName(),
                CredentialTablePresenter::owner(),
                CredentialTablePresenter::username(),
                CredentialTablePresenter::passwordColumn(),
                CredentialTablePresenter::link(),
                CredentialTablePresenter::createdAt(),
            ])
            ->groups([
                CredentialTablePresenter::appNameGroup(),
                CredentialTablePresenter::userGroup(),
            ])
            ->filters([
                CredentialTablePresenter::userFilter(),
                CredentialTablePresenter::hasLinkFilter(),
                CredentialTablePresenter::createdAtFilter(),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->striped()
            ->groupedBulkActions(self::bulkActions(CredentialExporter::class))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc');
    }
}
