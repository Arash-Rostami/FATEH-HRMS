<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseRequestResource\Exports\ReleaseRequestExporter;
use App\Filament\Resources\ReleaseRequestResource\Pages\CreateReleaseRequest;
use App\Filament\Resources\ReleaseRequestResource\Pages\EditReleaseRequest;
use App\Filament\Resources\ReleaseRequestResource\Pages\ListReleaseRequests;
use App\Filament\Resources\ReleaseRequestResource\Schemas\ReleaseRequestFormPresenter;
use App\Filament\Resources\ReleaseRequestResource\Schemas\ReleaseRequestInfolistPresenter;
use App\Filament\Resources\ReleaseRequestResource\Schemas\ReleaseRequestTablePresenter;
use App\Models\ReleaseRequest;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use App\Traits\FilamentAdminGuide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReleaseRequestResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission, FilamentAdminGuide;

    protected static ?string $model = ReleaseRequest::class;
    protected static ?string $recordTitleAttribute = 'title';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-sparkles';
    protected static ?int $navigationSort = 10;

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'menu_book', 'view' => 'filament.resources.release_request.guide.overview'],
        ['label' => 'عملیات ادمین', 'icon' => 'admin_panel_settings', 'view' => 'filament.resources.release_request.guide.admin-ops'],
        ['label' => 'زبانه‌ها و فیلترها', 'icon' => 'tab', 'view' => 'filament.resources.release_request.guide.list-tabs'],
        ['label' => 'تجربهٔ کاربر', 'icon' => 'visibility', 'view' => 'filament.resources.release_request.guide.user'],
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/release_request/strings.form.section_meta'))
                ->icon('heroicon-o-sparkles')
                ->description(__('resources/release_request/strings.form.section_meta_description'))
                ->schema([
                    ReleaseRequestFormPresenter::type(),
                    ReleaseRequestFormPresenter::status(),
                    ReleaseRequestFormPresenter::userId(),
                    ReleaseRequestFormPresenter::response(),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('resources/release_request/strings.form.section_content'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->description(__('resources/release_request/strings.form.section_content_description'))
                ->schema([
                    ReleaseRequestFormPresenter::title(),
                    ReleaseRequestFormPresenter::body(),
                    ReleaseRequestFormPresenter::attachments(),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user:id,name']);
    }

    public static function getModelLabel(): string
    {
        return __('resources/release_request/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/release_request/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReleaseRequests::route('/'),
            'create' => CreateReleaseRequest::route('/create'),
            'edit' => EditReleaseRequest::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/release_request/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    ReleaseRequestInfolistPresenter::id(),
                    ReleaseRequestInfolistPresenter::title(),
                    ReleaseRequestInfolistPresenter::body(),
                    ReleaseRequestInfolistPresenter::attachments(),
                    ReleaseRequestInfolistPresenter::user(),
                    ReleaseRequestInfolistPresenter::type(),
                    ReleaseRequestInfolistPresenter::status(),
                    ReleaseRequestInfolistPresenter::response(),
                    ReleaseRequestInfolistPresenter::createdAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ReleaseRequestTablePresenter::id(),
                ReleaseRequestTablePresenter::user(),
                ReleaseRequestTablePresenter::type(),
                ReleaseRequestTablePresenter::title(),
                ReleaseRequestTablePresenter::status(),
                ReleaseRequestTablePresenter::createdAt(),
            ])
            ->filters([
                ReleaseRequestTablePresenter::userFilter(),
                ReleaseRequestTablePresenter::typeFilter(),
                ReleaseRequestTablePresenter::statusFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                ReleaseRequestTablePresenter::reject(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(ReleaseRequestExporter::class))
            ->striped()
            ->emptyStateIcon('heroicon-o-sparkles')
            ->emptyStateActions(self::guideEmptyStateActions())
            ->defaultSort('created_at', 'desc');
    }
}
