<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SkillResource;
use App\Filament\Resources\SkillResource\Schemas\SkillFormPresenter;
use App\Models\Skill;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UnmetSkillDemandTable extends TableWidget
{
    protected static bool $isLazy = true;
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return SkillResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Skill::ghost()
                ->select(['id', 'name', 'name_en', 'category', 'description', 'icon', 'search_count', 'last_searched_at'])
                ->orderByDesc('search_count'))
            ->heading(__('resources/skill/strings.widget.title'))
            ->description(__('resources/skill/strings.widget.description'))
            ->emptyStateHeading(__('resources/skill/strings.table.no_ghosts'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('resources/skill/strings.fields.skill'))
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('search_count')
                    ->label(__('resources/skill/strings.fields.search_count'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_searched_at')
                    ->label(__('resources/skill/strings.fields.last_searched_at'))
                    ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '-')
                    ->color('gray'),
            ])
            ->recordActions([
                self::promoteAction(),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }

    public static function promoteAction(): Action
    {
        return Action::make('promote')
            ->label(__('resources/skill/strings.table.action_promote'))
            ->icon(SkillResource::getNavigationIcon())
            ->color('success')
            ->size('sm')
            ->visible(fn () => SkillResource::canCreate())
            ->modalIcon(SkillResource::getNavigationIcon())
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading(__('resources/skill/strings.table.action_promote'))
            ->fillForm(fn (Skill $record) => [
                'name_en' => $record->name_en,
                'category' => $record->category,
                'description' => $record->description,
                'icon' => $record->icon,
            ])
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name_en')
                                ->label(__('resources/skill/strings.fields.name_en'))
                                ->prefixIcon('heroicon-o-tag')
                                ->required()
                                ->maxLength(255),
                            SkillFormPresenter::category(),
                        ]),
                        SkillFormPresenter::icon()->columnSpanFull(),
                        Textarea::make('description')
                            ->label(__('resources/skill/strings.fields.description'))
                            ->rows(3)
                            ->autosize()
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->action(function (Skill $record, array $data): void {
                abort_unless(SkillResource::canCreate(), 403);

                $activated = DB::transaction(function () use ($data, $record): bool {
                    $fresh = Skill::lockForUpdate()->find($record->id);

                    if (!$fresh) {
                        return false;
                    }

                    $fresh->fill($data)->activate();

                    return true;
                });

                if ($activated) {
                    Notification::make()->title(__('resources/skill/strings.notifications.ghost_activated'))->success()->send();
                } else {
                    Notification::make()->title(__('resources/skill/strings.notifications.ghost_missing'))->warning()->send();
                }
            });
    }
}
