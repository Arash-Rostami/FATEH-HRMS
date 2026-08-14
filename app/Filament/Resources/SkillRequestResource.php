<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SkillRequestResource\Pages\ListSkillRequests;
use App\Filament\Resources\SkillRequestResource\Schemas\Helper\RequestActions;
use App\Filament\Resources\SkillRequestResource\Schemas\SkillRequestTablePresenter;
use App\Models\SkillUser;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentAdminGuide;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SkillRequestResource extends Resource
{
    use FilamentAdminGuide, FilamentFilters, RequestActions, AuthorizesByPermission;

    protected static ?string $model = SkillUser::class;
    protected static ?string $recordTitleAttribute = 'requested_name';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 14;

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'info', 'view' => 'filament.resources.skill_request.guide.overview'],
        ['label' => 'عملیات ادمین', 'icon' => 'admin_panel_settings', 'view' => 'filament.resources.skill_request.guide.admin-ops'],
        ['label' => 'زبانه‌ها و فیلترها', 'icon' => 'filter_alt', 'view' => 'filament.resources.skill_request.guide.list-tabs'],
        ['label' => 'سطح‌بندی و تأیید همکاران', 'icon' => 'workspace_premium', 'view' => 'filament.resources.skill_request.guide.tier'],
    ];


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user.profile.department',
                'skill',
                'approver'
            ]);
    }

    public static function getModelLabel(): string
    {
        return __('resources/skill_request/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/skill_request/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSkillRequests::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/skill_request/strings.plural_label');
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SkillRequestTablePresenter::user(),
                SkillRequestTablePresenter::skill(),
                SkillRequestTablePresenter::status(),
                SkillRequestTablePresenter::tier(),
                SkillRequestTablePresenter::endorsementsCount(),
                SkillRequestTablePresenter::lastUsedAt(),
                SkillRequestTablePresenter::createdAt(),
            ])
            ->filters([
                SkillRequestTablePresenter::statusFilter(),
                SkillRequestTablePresenter::departmentFilter(),
                SkillRequestTablePresenter::staleFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::approveAction(),
                self::rejectAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([
                self::bulkApproveAction(),
                self::bulkRejectAction(),
            ])
            ->striped()
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->defaultSort(fn(Builder $query): Builder => $query
                ->orderByRaw("status = 'pending' desc")
                ->orderBy('created_at', 'asc'));
    }

    private static function notifyCount(int $count, string $doneKey): void
    {
        if ($count > 0) {
            Notification::make()
                ->title(__('resources/skill_request/strings.notifications.' . $doneKey, ['count' => $count]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('resources/skill_request/strings.notifications.nothing_pending'))
                ->warning()
                ->send();
        }
    }
}
