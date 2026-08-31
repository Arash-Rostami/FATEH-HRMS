<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\ProfileResource\Schemas\ProfileFormPresenter;
use App\Filament\Resources\ProfileResource\Schemas\ProfileInfolistPresenter;
use App\Filament\Resources\ProfileResource\Schemas\ProfileTablePresenter;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProfileRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'profile';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/profile/strings.form.section_identity'))
                        ->icon('heroicon-o-user-circle')
                        ->schema([
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
                                ->columns(3)
                                ->columnSpanFull(),

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
                                ->columns(3)
                                ->columnSpanFull(),

                            Section::make(__('resources/profile/strings.form.section_employment'))
                                ->schema([
                                    ProfileFormPresenter::employmentType(),
                                    ProfileFormPresenter::employmentStatus(),
                                    ProfileFormPresenter::degree(),
                                    ProfileFormPresenter::departmentId(),
                                    ProfileFormPresenter::position(),
                                    ProfileFormPresenter::workExperience(),
                                    ProfileFormPresenter::insurance(),
                                    ProfileFormPresenter::startDate(),
                                    ProfileFormPresenter::endDate(),
                                    ProfileFormPresenter::field(),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Tab::make(__('resources/profile/strings.form.section_media'))
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            ProfileFormPresenter::image(),
                            ProfileFormPresenter::attachments(),
                        ])
                        ->columns(2),

                    Tab::make(__('resources/profile/strings.form.section_about'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            ProfileFormPresenter::interests(),
                            ProfileFormPresenter::aboutMe(),
                            ProfileFormPresenter::favoriteColors(),
                        ])
                        ->columns(2),
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

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/profile/strings.navigation.plural');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/profile/strings.infolist.section_identity'))
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Section::make(__('resources/profile/strings.infolist.section_identity'))
                                ->schema([
                                    ProfileInfolistPresenter::id(),
                                    ProfileInfolistPresenter::personnelId(),
                                    ProfileInfolistPresenter::gender(),
                                    ProfileInfolistPresenter::idCardNumber(),
                                    ProfileInfolistPresenter::idBookletNumber(),
                                    ProfileInfolistPresenter::birthdate(),
                                    ProfileInfolistPresenter::age(),
                                    ProfileInfolistPresenter::maritalStatus(),
                                    ProfileInfolistPresenter::numberOfChildren(),
                                ])
                                ->columns(2)
                                ->columnSpan(1),

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
                                ->columns(2)
                                ->columnSpan(1),
                        ])
                        ->columns(2),

                    Tab::make(__('resources/profile/strings.infolist.section_employment'))
                        ->icon('heroicon-o-briefcase')
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

                    Tab::make(__('resources/profile/strings.infolist.section_media'))
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            ProfileInfolistPresenter::image(),
                            ProfileInfolistPresenter::attachments(),
                        ])
                        ->columns(2),

                    Tab::make(__('resources/profile/strings.infolist.section_about'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            ProfileInfolistPresenter::interests(),
                            ProfileInfolistPresenter::favoriteColors(),
                            ProfileInfolistPresenter::aboutMe(),
                            ProfileInfolistPresenter::createdAt(),
                            ProfileInfolistPresenter::updatedAt(),
                        ])
                        ->columns(2),
                ])
                ->columnSpanFull()
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['department', 'user']))
            ->columns([
                ProfileTablePresenter::id(),
                ProfileTablePresenter::avatar(),
                ProfileTablePresenter::position(),
                ProfileTablePresenter::employmentStatus(),
                ProfileTablePresenter::personnelId(),
                ProfileTablePresenter::department(),
                ProfileTablePresenter::employmentType(),
                ProfileTablePresenter::gender(),
                ProfileTablePresenter::cellphone(),
                ProfileTablePresenter::startDate(),
                ProfileTablePresenter::createdAt(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label(__('resources/profile/strings.navigation.singular'))
                    ->visible(fn (): bool => blank($this->getOwnerRecord()->profile)),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->striped()
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
