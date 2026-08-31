<?php

namespace App\Filament\Resources\FeedResource\RelationManagers;

use App\Models\Poll;
use App\Traits\FilamentActions;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PollsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'polls';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/feed/strings.fields.polls_count');
    }

    protected function resolveChoices(Poll $record): string
    {
        $options = $this->ownerRecord->pollChoices();

        $texts = [];

        foreach (explode(',', (string) $record->option_indexes) as $raw) {
            $raw = trim($raw);

            if ($raw === '' || !ctype_digit($raw)) {
                continue;
            }

            $i = (int) $raw;

            if (isset($options[$i])) {
                $texts[] = $options[$i];
            }
        }

        return $texts ? implode('، ', $texts) : '—';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('user.name')
                    ->label(__('resources/feed/strings.poll.voter'))
                    ->icon('heroicon-o-user')
                    ->placeholder('—'),

                TextEntry::make('choices')
                    ->label(__('resources/feed/strings.fields.poll_option'))
                    ->getStateUsing(fn (Poll $record): string => $this->resolveChoices($record))
                    ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
                    ->size(TextSize::Medium),

                TextEntry::make('votes_count')
                    ->label(__('resources/feed/strings.poll.votes_count'))
                    ->getStateUsing(fn (Poll $record): int => (int) $record->votes_count)
                    ->badge()
                    ->color(fn (Poll $record): string => (int) $record->votes_count > 1 ? 'warning' : 'success'),

                TextEntry::make('created_at')
                    ->label(__('resources/feed/strings.fields.created_at'))
                    ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '—')
                    ->icon('heroicon-o-clock')
                    ->color('gray'),
            ])->columnSpanFull()
                ->columns(4),
        ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        $ownerId = $this->ownerRecord->id;

        return $table
            ->query(
                Poll::query()
                    ->fromSub(function ($query) use ($ownerId) {
                        $query->from('polls')
                            ->where('feed_id', $ownerId)
                            ->selectRaw('user_id, MIN(id) as id, GROUP_CONCAT(option_index ORDER BY option_index SEPARATOR ",") as option_indexes, COUNT(*) as votes_count, MIN(created_at) as created_at')
                            ->groupBy('user_id');
                    }, 'polls')
                    ->with('user')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('resources/feed/strings.poll.voter'))
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('choices')
                    ->label(__('resources/feed/strings.fields.poll_option'))
                    ->getStateUsing(fn (Poll $record): string => $this->resolveChoices($record))
                    ->limit(80)
                    ->tooltip(fn (Poll $record): ?string => ($c = $this->resolveChoices($record)) !== '—' ? $c : null)
                    ->wrap()
                    ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;']),

                TextColumn::make('votes_count')
                    ->label(__('resources/feed/strings.poll.votes_count'))
                    ->getStateUsing(fn (Poll $record): int => (int) $record->votes_count)
                    ->badge()
                    ->color(fn (Poll $record): string => (int) $record->votes_count > 1 ? 'warning' : 'success')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label(__('resources/feed/strings.fields.created_at'))
                    ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '—')
                    ->icon('heroicon-o-clock'),
            ])
            ->filters([
                Filter::make('voter_type')
                    ->label(__('resources/feed/strings.poll.filter_voter_type'))
                    ->schema([
                        Select::make('mode')
                            ->label(__('resources/feed/strings.poll.filter_voter_mode'))
                            ->options([
                                'single'    => __('resources/feed/strings.poll.mode_single'),
                                'multiple'  => __('resources/feed/strings.poll.mode_multiple'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $mode = $data['mode'] ?? null;

                        if ($mode === 'single') {
                            return $query->whereRaw('votes_count = 1');
                        }

                        if ($mode === 'multiple') {
                            return $query->whereRaw('votes_count > 1');
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                self::viewAction(),
                DeleteAction::make()
                    ->tooltip(__('resources/general/strings.table.action_delete'))
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading(__('resources/feed/strings.poll.delete_voter_heading'))
                    ->modalDescription(__('resources/feed/strings.poll.delete_voter_body'))
                    ->action(function (Model $record) use ($ownerId): void {
                        Poll::query()
                            ->where('feed_id', $ownerId)
                            ->where('user_id', $record->user_id)
                            ->delete();

                        Notification::make()
                            ->title(__('resources/feed/strings.poll.voter_deleted'))
                            ->success()
                            ->send();
                    }),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([
                BulkAction::make('delete')
                    ->label(__('resources/general/strings.table.bulk_delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) use ($ownerId): void {
                        $voters = $records->pluck('user_id')->filter()->unique()->all();

                        Poll::query()
                            ->where('feed_id', $ownerId)
                            ->whereIn('user_id', $voters)
                            ->delete();

                        Notification::make()
                            ->title(__('resources/feed/strings.poll.voter_deleted'))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-chart-bar')
            ->striped();
    }
}
