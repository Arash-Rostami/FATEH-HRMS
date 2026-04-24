<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\ProfileResource\Schemas\ProfileFormPresenter;
use App\Filament\Resources\ProfileResource\Schemas\ProfileInfolistPresenter;
use App\Filament\Resources\ProfileResource\Schemas\ProfileTablePresenter;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class ProfileRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'profile';

    protected static ?string $title = 'پروفایل';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/profile/strings.form.section_identity'))
                ->schema([
                    ProfileFormPresenter::personnelId(),
                    ProfileFormPresenter::gender(),
                    ProfileFormPresenter::idCardNumber(),
                    ProfileFormPresenter::idBookletNumber(),
                    ProfileFormPresenter::birthdate(),
                    ProfileFormPresenter::maritalStatus(),
                    ProfileFormPresenter::numberOfChildren(),
                ])
                ->columns(2),

            Section::make(__('resources/profile/strings.form.section_employment'))
                ->schema([
                    ProfileFormPresenter::departmentId(),
                    ProfileFormPresenter::position(),
                    ProfileFormPresenter::employmentType(),
                    ProfileFormPresenter::employmentStatus(),
                    ProfileFormPresenter::degree(),
                    ProfileFormPresenter::field(),
                    ProfileFormPresenter::insurance(),
                    ProfileFormPresenter::startDate(),
                    ProfileFormPresenter::endDate(),
                    ProfileFormPresenter::workExperience(),
                ])
                ->columns(2),

            Section::make(__('resources/profile/strings.form.section_contact'))
                ->schema([
                    ProfileFormPresenter::cellphone(),
                    ProfileFormPresenter::landline(),
                    ProfileFormPresenter::emergencyPhone(),
                    ProfileFormPresenter::emergencyRelationship(),
                    ProfileFormPresenter::licensePlate(),
                    ProfileFormPresenter::zipCode(),
                    ProfileFormPresenter::address(),
                    ProfileFormPresenter::accessibility(),
                ])
                ->columns(2),

            Section::make(__('resources/profile/strings.form.section_media'))
                ->schema([
                    ProfileFormPresenter::image(),
                    ProfileFormPresenter::attachments(),
                ])
                ->columnSpanFull(),

            Section::make(__('resources/profile/strings.form.section_personal'))
                ->schema([
                    ProfileFormPresenter::interests(),
                    ProfileFormPresenter::favoriteColors(),
                ])
                ->columnSpanFull(),

            Section::make(__('resources/profile/strings.form.section_about'))
                ->schema([
                    ProfileFormPresenter::aboutMe(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function getModelLabel(): string
    {
        return __('resources/profile/strings.navigation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/profile/strings.navigation.plural');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/profile/strings.infolist.section_identity'))
                ->schema([
                    ProfileInfolistPresenter::personnelId(),
                    ProfileInfolistPresenter::gender(),
                    ProfileInfolistPresenter::idCardNumber(),
                    ProfileInfolistPresenter::idBookletNumber(),
                    ProfileInfolistPresenter::birthdate(),
                    ProfileInfolistPresenter::age(),
                    ProfileInfolistPresenter::maritalStatus(),
                    ProfileInfolistPresenter::numberOfChildren(),
                ])
                ->columns(2),

            Section::make(__('resources/profile/strings.infolist.section_employment'))
                ->schema([
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
                ])
                ->columns(2),

            Section::make(__('resources/profile/strings.infolist.section_contact'))
                ->schema([
                    ProfileInfolistPresenter::cellphone(),
                    ProfileInfolistPresenter::landline(),
                    ProfileInfolistPresenter::emergencyPhone(),
                    ProfileInfolistPresenter::emergencyRelationship(),
                    ProfileInfolistPresenter::licensePlate(),
                    ProfileInfolistPresenter::zipCode(),
                    ProfileInfolistPresenter::address(),
                    ProfileInfolistPresenter::accessibility(),
                ])
                ->columns(2),

            Section::make(__('resources/profile/strings.infolist.section_media'))
                ->schema([
                    ProfileInfolistPresenter::image(),
                    ProfileInfolistPresenter::attachments(),
                ])
                ->columnSpanFull(),

            Section::make(__('resources/profile/strings.infolist.section_personal'))
                ->schema([
                    ProfileInfolistPresenter::interests(),
                    ProfileInfolistPresenter::favoriteColors(),
                ])
                ->columnSpanFull(),

            Section::make(__('resources/profile/strings.infolist.section_about'))
                ->schema([
                    ProfileInfolistPresenter::aboutMe(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ProfileTablePresenter::id(),
                ProfileTablePresenter::position(),
                ProfileTablePresenter::employmentStatus(),
                ProfileTablePresenter::department(),
                ProfileTablePresenter::employmentType(),
                ProfileTablePresenter::gender(),
                ProfileTablePresenter::cellphone(),
                ProfileTablePresenter::startDate(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label('افزودن پروفایل'),
            ])
            ->searchable(false)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
