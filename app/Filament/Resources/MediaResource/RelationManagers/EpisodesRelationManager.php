<?php

namespace App\Filament\Resources\MediaResource\RelationManagers;

use App\Filament\RelationManagers\RelationManager;
use App\Filament\Resources\MediaResource;
use App\Filament\Resources\MediaResource\Schemas\MediaFormPresenter;
use App\Filament\Resources\MediaResource\Schemas\MediaInfolistPresenter;
use App\Filament\Resources\MediaResource\Schemas\MediaTablePresenter;
use App\Models\Media;
use App\Services\Cache\ModelCacheVersion;
use App\Traits\Filament\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EpisodesRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'episodes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->kind === 'series' && parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/media/strings.relation.episodes');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->extraAttributes(['class' => 'fi-infolist-panel'])
                ->hiddenLabel()
                ->schema([
                    MediaInfolistPresenter::cover(),
                    MediaInfolistPresenter::title(),
                    MediaInfolistPresenter::kind(),
                    MediaInfolistPresenter::duration(),

                    MediaInfolistPresenter::divider(),
                    MediaInfolistPresenter::active(),
                    MediaInfolistPresenter::pinned(),

                    MediaInfolistPresenter::divider(),
                    MediaInfolistPresenter::reportDate(),
                    MediaInfolistPresenter::expiresAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/media/strings.form.tab_main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make(__('resources/media/strings.relation.episode_details'))
                                ->description(__('resources/media/strings.relation.episode_hint'))
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    MediaFormPresenter::episodeKind(),
                                    MediaFormPresenter::title(),
                                    MediaFormPresenter::topic(),
                                    MediaFormPresenter::icon(),
                                    MediaFormPresenter::active()->helperText(__('resources/media/strings.hints.episode_active')),
                                    MediaFormPresenter::divider(),
                                    MediaFormPresenter::reportDate(),
                                    MediaFormPresenter::expiresAt(),
                                    MediaFormPresenter::divider(),
                                    MediaFormPresenter::description(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    Tab::make(__('resources/media/strings.form.tab_files'))
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            Section::make(__('resources/media/strings.form.section_files'))
                                ->icon('heroicon-o-folder-open')
                                ->schema([
                                    MediaFormPresenter::coverImage(),
                                    MediaFormPresenter::divider(),
                                    MediaFormPresenter::filePath(),
                                    MediaFormPresenter::durationSeconds(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make(__('resources/media/strings.form.section_tracks'))
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    MediaFormPresenter::transcriptPath(),
                                    MediaFormPresenter::chaptersPath(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['department']))
            ->columns([
                MediaTablePresenter::id(),
                MediaTablePresenter::title(),
                MediaTablePresenter::kind(),
                MediaTablePresenter::duration(),
                MediaResource::mediaActiveColumn(),
                MediaTablePresenter::reportDate(),
            ])
            ->reorderable('position')
            ->afterReordering(function (): void {
                ModelCacheVersion::bump(Media::class);
            })
            ->defaultSort('position', 'asc')
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label(__('resources/media/strings.relation.add_episode'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['parent_id'] = $this->getOwnerRecord()->getKey();

                        return $data;
                    }),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction()->using(fn(Model $record, array $data): Model => $this->updateDeletingDroppedFiles($record, $data, [
                    'cover_image' => [],
                    'file_path' => [],
                    'transcript_path' => [],
                    'chapters_path' => [],
                ])),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateHeading(__('resources/media/strings.relation.empty_heading'))
            ->emptyStateDescription(__('resources/media/strings.relation.empty_description'))
            ->striped();
    }
}
