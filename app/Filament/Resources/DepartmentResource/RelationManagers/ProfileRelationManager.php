<?php
namespace App\Filament\Resources\DepartmentResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\ProfileResource\Schemas\ProfileFormPresenter;
use App\Filament\Resources\ProfileResource\Schemas\ProfileInfolistPresenter;
use App\Filament\Resources\ProfileResource\Schemas\ProfileTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class ProfileRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'profile';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Profile/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    ProfileFormPresenter::aboutMe(),
                    ProfileFormPresenter::accessibility(),
                    ProfileFormPresenter::address(),
                    ProfileFormPresenter::attachments(),
                    ProfileFormPresenter::birthdate(),
                    ProfileFormPresenter::cellphone(),
                    ProfileFormPresenter::degree(),
                    ProfileFormPresenter::departmentId(),
                    ProfileFormPresenter::emergencyPhone(),
                    ProfileFormPresenter::emergencyRelationship(),
                    ProfileFormPresenter::employmentStatus(),
                    ProfileFormPresenter::employmentType(),
                    ProfileFormPresenter::endDate(),
                    ProfileFormPresenter::favoriteColors(),
                    ProfileFormPresenter::field(),
                    ProfileFormPresenter::gender(),
                    ProfileFormPresenter::idBookletNumber(),
                    ProfileFormPresenter::idCardNumber(),
                    ProfileFormPresenter::image(),
                    ProfileFormPresenter::insurance(),
                    ProfileFormPresenter::interests(),
                    ProfileFormPresenter::landline(),
                    ProfileFormPresenter::licensePlate(),
                    ProfileFormPresenter::maritalStatus(),
                    ProfileFormPresenter::numberOfChildren(),
                    ProfileFormPresenter::personnelId(),
                    ProfileFormPresenter::position(),
                    ProfileFormPresenter::startDate(),
                    ProfileFormPresenter::userId(),
                    ProfileFormPresenter::workExperience(),
                    ProfileFormPresenter::zipCode(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Profile/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Profile/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Profile/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Profile/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    ProfileInfolistPresenter::id(),
                    ProfileInfolistPresenter::personnelId(),
                    ProfileInfolistPresenter::userName(),
                    ProfileInfolistPresenter::gender(),
                    ProfileInfolistPresenter::idCardNumber(),
                    ProfileInfolistPresenter::idBookletNumber(),
                    ProfileInfolistPresenter::birthdate(),
                    ProfileInfolistPresenter::age(),
                    ProfileInfolistPresenter::maritalStatus(),
                    ProfileInfolistPresenter::numberOfChildren(),
                    ProfileInfolistPresenter::department(),
                    ProfileInfolistPresenter::position(),
                    ProfileInfolistPresenter::employmentType(),
                    ProfileInfolistPresenter::employmentStatus(),
                    ProfileInfolistPresenter::degree(),
                    ProfileInfolistPresenter::field(),
                    ProfileInfolistPresenter::insurance(),
                    ProfileInfolistPresenter::startDate(),
                    ProfileInfolistPresenter::endDate(),
                    ProfileInfolistPresenter::workExperience(),
                    ProfileInfolistPresenter::cellphone(),
                    ProfileInfolistPresenter::landline(),
                    ProfileInfolistPresenter::emergencyPhone(),
                    ProfileInfolistPresenter::emergencyRelationship(),
                    ProfileInfolistPresenter::licensePlate(),
                    ProfileInfolistPresenter::zipCode(),
                    ProfileInfolistPresenter::address(),
                    ProfileInfolistPresenter::accessibility(),
                    ProfileInfolistPresenter::image(),
                    ProfileInfolistPresenter::attachments(),
                    ProfileInfolistPresenter::interests(),
                    ProfileInfolistPresenter::favoriteColors(),
                    ProfileInfolistPresenter::aboutMe(),
                    ProfileInfolistPresenter::createdAt(),
                    ProfileInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ProfileTablePresenter::avatar(),
                ProfileTablePresenter::cellphone(),
                ProfileTablePresenter::createdAt(),
                ProfileTablePresenter::department(),
                ProfileTablePresenter::employmentStatus(),
                ProfileTablePresenter::employmentType(),
                ProfileTablePresenter::gender(),
                ProfileTablePresenter::id(),
                ProfileTablePresenter::personnelId(),
                ProfileTablePresenter::position(),
                ProfileTablePresenter::startDate(),
                ProfileTablePresenter::userName(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Profile/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
