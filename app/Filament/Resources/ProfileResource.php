<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Exports\ProfileExporter;
use App\Filament\Resources\ProfileResource\Pages\CreateProfile;
use App\Filament\Resources\ProfileResource\Pages\EditProfile;
use App\Filament\Resources\ProfileResource\Pages\ListProfiles;
use App\Filament\Resources\ProfileResource\RelationManagers\UserRelationManager;
use App\Filament\Resources\ProfileResource\Schemas\ProfileFormPresenter;
use App\Filament\Resources\ProfileResource\Schemas\ProfileInfolistPresenter;
use App\Filament\Resources\ProfileResource\Schemas\ProfileTablePresenter;
use App\Models\Profile;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProfileResource extends Resource
{
    use FilamentActions, AuthorizesByPermission;

    protected static ?string $model = Profile::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-identification';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/profile/strings.form.section_identity_header'))
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Section::make(__('resources/profile/strings.form.section_identity'))
                                ->schema([
                                    ProfileFormPresenter::userId(),
                                    ProfileFormPresenter::birthdate(),
                                    ProfileFormPresenter::gender(),
                                    ProfileFormPresenter::maritalStatus(),
                                    ProfileFormPresenter::numberOfChildren(),
                                    ProfileFormPresenter::personnelId(),
                                    ProfileFormPresenter::idCardNumber(),
                                    ProfileFormPresenter::idBookletNumber(),

                                ])
                                ->columns(2)
                                ->columnSpan(1),

                            Section::make(__('resources/profile/strings.form.section_contact'))
                                ->icon('heroicon-o-phone')
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
                                ->columns(2)
                                ->columnSpan(1),

                            Section::make(__('resources/profile/strings.form.section_employment'))
                                ->icon('heroicon-o-briefcase')
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
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'department']);
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
            ' شماره پرسنلی' => $record->personnel_id ?? '—',
            'کارت ملی' => $record->id_card_number ?? '—',
            'شماره شناسنامه' => $record->id_booklet_number ?? '—',
            'موبایل' => $record->cellphone ?? '—',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->user->name;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'user.name',
            'personnel_id',
            'id_card_number',
            'id_booklet_number',
            'cellphone',
        ];
    }

    public static function getModelLabel(): string
    {
        return __('resources/profile/strings.navigation.singular');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/profile/strings.navigation.group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProfiles::route('/'),
            'create' => CreateProfile::route('/create'),
            'edit' => EditProfile::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/profile/strings.navigation.plural');
    }

    public static function getRelations(): array
    {
        return [
            UserRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
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
                                    ProfileInfolistPresenter::userName(),
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
                ->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ProfileTablePresenter::id(),
                ProfileTablePresenter::avatar(),
                ProfileTablePresenter::userName(),
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
            ->groups([
                ProfileTablePresenter::departmentGroup(),
                ProfileTablePresenter::positionGroup(),
                ProfileTablePresenter::employmentStatusGroup(),
                ProfileTablePresenter::employmentTypeGroup(),
                ProfileTablePresenter::genderGroup(),
            ])
            ->filters([
                ProfileTablePresenter::employmentStatusFilter(),
                ProfileTablePresenter::employmentTypeFilter(),
                ProfileTablePresenter::genderFilter(),
                ProfileTablePresenter::degreeFilter(),
                ProfileTablePresenter::departmentFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->striped()
            ->groupedBulkActions(self::bulkActions(ProfileExporter::class))
            ->emptyStateIcon('heroicon-o-identification')
            ->defaultSort('id', 'desc');
    }
}
