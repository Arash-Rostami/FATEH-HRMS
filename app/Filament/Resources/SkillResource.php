<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SkillResource\Pages\CreateSkill;
use App\Filament\Resources\SkillResource\Pages\EditSkill;
use App\Filament\Resources\SkillResource\Pages\ListSkills;
use App\Filament\Resources\SkillResource\RelationManagers\MembersRelationManager;
use App\Filament\Resources\SkillResource\Schemas\SkillFormPresenter;
use App\Filament\Resources\SkillResource\Schemas\SkillInfolistPresenter;
use App\Filament\Resources\SkillResource\Schemas\SkillTablePresenter;
use App\Models\Skill;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentAdminGuide;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SkillResource extends Resource
{
    use FilamentActions, FilamentFilters, FilamentAdminGuide, AuthorizesByPermission;

    protected static ?string $model = Skill::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-bolt';
    protected static ?int $navigationSort = 13;

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'bolt', 'view' => 'filament.resources.skill.guide.overview'],
        ['label' => 'جستجوی تأمین‌نشده', 'icon' => 'auto_awesome', 'view' => 'filament.resources.skill.guide.ghost'],
        ['label' => 'اشتراک نام و فعال‌سازی', 'icon' => 'verified_user', 'view' => 'filament.resources.skill.guide.collision'],
        ['label' => 'عملیات ادمین', 'icon' => 'admin_panel_settings', 'view' => 'filament.resources.skill.guide.admin-ops'],
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/skill/strings.form.section_identity'))
                ->icon('heroicon-o-bolt')
                ->description(__('resources/skill/strings.form.section_identity_description'))
                ->schema([
                    SkillFormPresenter::name(),
                    SkillFormPresenter::nameEn(),
                    SkillFormPresenter::category(),
                    SkillFormPresenter::icon(),
                    SkillFormPresenter::isActive(),
                    SkillFormPresenter::divider(),
                    SkillFormPresenter::description(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_ghost', false)->withCount('skillUsers');
    }

    public static function getModelLabel(): string
    {
        return __('resources/skill/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/skill/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSkills::route('/'),
            'create' => CreateSkill::route('/create'),
            'edit' => EditSkill::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/skill/strings.plural_label');
    }

    public static function getRelations(): array
    {
        return [MembersRelationManager::class];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                SkillInfolistPresenter::name(),
                SkillInfolistPresenter::nameEn(),
                SkillInfolistPresenter::category(),
                SkillInfolistPresenter::icon(),
                SkillInfolistPresenter::isActive(),
                SkillInfolistPresenter::membersCount(),
                SkillInfolistPresenter::description(),
                SkillInfolistPresenter::createdAt(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SkillTablePresenter::name(),
                SkillTablePresenter::category(),
                SkillTablePresenter::isActive(),
                SkillTablePresenter::membersCount(),
                SkillTablePresenter::createdAt(),
            ])
            ->filters([
                SkillTablePresenter::categoryFilter(),
                SkillTablePresenter::isActiveFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->groups([
                SkillTablePresenter::categoryGroup(),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction()->hidden(fn(Skill $record): bool => $record->is_ghost),
                self::deleteAction()->hidden(fn($record) => ($record->skill_users_count ?? 0) > 0),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([
                self::bulkDeleteAction()
                    ->action(fn(Collection $records) => $records->filter(fn($record) => ($record->skill_users_count ?? 0) === 0)->each->delete())
            ])
            ->striped()
            ->emptyStateIcon('heroicon-o-bolt')
            ->defaultSort('id', 'desc');
    }
}
