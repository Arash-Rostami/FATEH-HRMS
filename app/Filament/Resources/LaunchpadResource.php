<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaunchpadResource\Pages\ListLaunchpads;
use App\Filament\Resources\LaunchpadResource\Schemas\LaunchpadTablePresenter;
use App\Models\Launchpad;
use App\Traits\Filament\{AuthorizesByPermission, FilamentActions, FilamentAdminGuide, FilamentFilters, FilamentVisibility};
use Filament\Resources\Resource;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LaunchpadResource extends Resource
{
    use AuthorizesByPermission, FilamentActions, FilamentAdminGuide, FilamentFilters, FilamentVisibility;

    protected static ?string $model = Launchpad::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'menu_book', 'view' => 'filament.resources.launchpad.guide.overview'],
    ];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user:id,name');
    }

    public static function getModelLabel(): string
    {
        return __('resources/launchpad/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/launchpad/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLaunchpads::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/launchpad/strings.plural_label');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                LaunchpadTablePresenter::title(),
                LaunchpadTablePresenter::kind(),
                LaunchpadTablePresenter::department(),
                LaunchpadTablePresenter::owner(),
                self::activeColumn()->updateStateUsing(LaunchpadTablePresenter::toggleActive()),
                LaunchpadTablePresenter::audienceCount(),
                LaunchpadTablePresenter::status(),
                LaunchpadTablePresenter::refreshed(),
                LaunchpadTablePresenter::expiry(),
                LaunchpadTablePresenter::created(),
            ])
            ->groups([
                LaunchpadTablePresenter::departmentGroup(),
            ])
            ->filters([
                LaunchpadTablePresenter::departmentFilter(),
                self::activeFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                LaunchpadTablePresenter::reassignOwner()->visible(fn (): bool => static::canEditAny()),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([
                static::bulkDeleteAction(),
                self::bulkActivateAction()->action(LaunchpadTablePresenter::bulkSetActive(true)),
                self::bulkDeactivateAction()->action(LaunchpadTablePresenter::bulkSetActive(false)),
            ])
            ->emptyStateIcon('heroicon-o-rocket-launch')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
