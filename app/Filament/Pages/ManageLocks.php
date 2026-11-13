<?php

namespace App\Filament\Pages;

use App\Models\Channel;
use App\Models\Lock;
use App\Models\User;
use App\Services\Lock\ClearModuleLock;
use App\Services\Lock\SetModuleLock;
use App\Support\LockPolicy;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ManageLocks extends Page implements HasTable
{
    use InteractsWithTable;

    private const ALL_MODULES = '*';

    protected static ?string $slug = 'module-locks';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    public static function canAccess(): bool
    {
        return LockPolicy::isHolder(Auth::user());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function boot(): void
    {
        abort_unless(static::canAccess(), 404);
    }

    public static function getNavigationLabel(): string
    {
        return __('resources/lock/strings.manage.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('resources/lock/strings.manage.title');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Lock::query()
                ->when(
                    Auth::user()->isDeveloper(),
                    fn ($query) => $query->with('channel.owner:id,name'),
                    fn ($query) => $query->where('lockable_id', 0)->where('slot', Lock::slotFor(Auth::user())),
                )
                ->orderBy('lockable_type')
                ->orderBy('lockable_id'))
            ->columns([
                TextColumn::make('lockable_type')
                    ->label(__('resources/lock/strings.manage.module'))
                    ->formatStateUsing(fn (string $state): string => LockPolicy::lockableModules()[$state] ?? $state)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereIn('lockable_type', collect(LockPolicy::lockableModules())->filter(fn (string $label): bool => mb_stripos($label, $search) !== false)->keys()->all())
                        ->orWhere('lockable_type', 'like', "%{$search}%")),
                TextColumn::make('channel.name')
                    ->label(__('resources/lock/strings.manage.record'))
                    ->placeholder('—')
                    ->searchable()
                    ->visible(fn (): bool => Auth::user()->isDeveloper()),
                TextColumn::make('channel.owner.name')
                    ->label(__('resources/lock/strings.manage.owner'))
                    ->placeholder('—')
                    ->searchable()
                    ->visible(fn (): bool => Auth::user()->isDeveloper()),
                TextColumn::make('slot')
                    ->label(__('resources/lock/strings.manage.slot'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("resources/lock/strings.slots.{$state}")),
                TextColumn::make('updated_at')
                    ->label(__('resources/lock/strings.manage.updated'))
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('slot')
                    ->label(__('resources/lock/strings.manage.slot'))
                    ->options(fn (): array => collect([...Lock::SLOTS, Lock::SLOT_OWNER])->mapWithKeys(fn (string $slot): array => [$slot => __("resources/lock/strings.slots.{$slot}")])->all())
                    ->default(Lock::SLOT_MASTER)
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) && blank($this->tableFilters['channel_owner']['value'] ?? null)
                        ? $query->where('slot', $data['value'])
                        : $query)
                    ->visible(fn (): bool => Auth::user()->isDeveloper()),
                SelectFilter::make('channel_owner')
                    ->label(__('resources/lock/strings.manage.owner'))
                    ->options(fn (): array => $this->keyOwners())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('slot', Lock::SLOT_OWNER)->whereHas('channel', fn (Builder $channel) => $channel->where('owner_id', $data['value']))
                        : $query)
                    ->visible(fn (): bool => Auth::user()->isDeveloper()),
            ])
            ->recordActions([$this->changeAction(), $this->clearAction()], RecordActionsPosition::AfterCells)
            ->searchPlaceholder(__('resources/lock/strings.manage.search'))
            ->groupedBulkActions([$this->changeManyAction(), $this->clearManyAction()])
            ->emptyStateIcon('heroicon-o-lock-open')
            ->emptyStateHeading(__('resources/lock/strings.manage.empty_heading'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('set')
                ->label(__('resources/lock/strings.manage.set'))
                ->icon('heroicon-o-lock-closed')
                ->schema([
                    Select::make('modules')
                        ->label(__('resources/lock/strings.manage.modules'))
                        ->options(fn (): array => ($available = $this->availableModules()) === [] ? [] : [self::ALL_MODULES => __('resources/lock/strings.manage.all_modules')] + $available)
                        ->multiple()
                        ->required(),
                    ...$this->codeFields(),
                ])
                ->action(function (array $data): void {
                    $modules = in_array(self::ALL_MODULES, $data['modules'], true) ? array_keys($this->availableModules()) : $data['modules'];
                    $locks = app(SetModuleLock::class)->handleMany(Auth::user(), $modules, $data['code']);
                    Notification::make()->title(__('resources/lock/strings.manage.saved_many', ['count' => convertToPersian($locks->count())]))->success()->send();
                }),
        ];
    }

    private function changeAction(): Action
    {
        return Action::make('change')
            ->label(__('resources/lock/strings.manage.change'))
            ->icon('heroicon-m-key')
            ->iconButton()
            ->visible(fn (Lock $record): bool => $this->mayChange($record))
            ->schema($this->codeFields())
            ->action(function (Lock $record, array $data): void {
                app(SetModuleLock::class)->handle(Auth::user(), $record->lockable_type, $data['code'], $record->slot);
                Notification::make()->title(__('resources/lock/strings.manage.saved'))->success()->send();
            });
    }

    private function clearAction(): Action
    {
        return Action::make('clear')
            ->label(__('resources/lock/strings.manage.clear'))
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->iconButton()
            ->visible(fn (Lock $record): bool => $this->mayTouch($record))
            ->requiresConfirmation()
            ->modalDescription(__('resources/lock/strings.manage.clear_confirm'))
            ->action(function (Lock $record): void {
                $this->clearLock($record);
                Notification::make()->title(__('resources/lock/strings.manage.cleared'))->success()->send();
            });
    }

    private function availableModules(): array
    {
        $taken = Lock::query()
            ->where('lockable_id', 0)
            ->where('slot', Lock::slotFor(Auth::user()))
            ->pluck('lockable_type')
            ->all();

        return array_diff_key(LockPolicy::lockableModules(), array_flip($taken));
    }

    private function changeManyAction(): BulkAction
    {
        return BulkAction::make('changeMany')
            ->label(__('resources/lock/strings.manage.change'))
            ->icon('heroicon-m-key')
            ->schema($this->codeFields())
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data): void {
                $eligible = $records->filter(fn (Lock $record): bool => $this->mayChange($record));

                $eligible->groupBy('slot')->each(fn (Collection $group, string $slot) => app(SetModuleLock::class)
                    ->handleMany(Auth::user(), $group->pluck('lockable_type')->all(), $data['code'], $slot));

                Notification::make()->title(__('resources/lock/strings.manage.saved_many', ['count' => convertToPersian($eligible->count())]))->success()->send();
            });
    }

    private function clearManyAction(): BulkAction
    {
        return BulkAction::make('clearMany')
            ->label(__('resources/lock/strings.manage.clear'))
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(__('resources/lock/strings.manage.clear_confirm'))
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $cleared = $records
                    ->filter(fn (Lock $record): bool => $this->mayTouch($record))
                    ->each(fn (Lock $record) => $this->clearLock($record));

                Notification::make()->title(__('resources/lock/strings.manage.cleared_many', ['count' => convertToPersian($cleared->count())]))->success()->send();
            });
    }

    private function mayTouch(Lock $record): bool
    {
        return (int) $record->lockable_id > 0
            ? Auth::user()->isDeveloper()
            : LockPolicy::manages(Auth::user(), $record->slot);
    }

    private function mayChange(Lock $record): bool
    {
        return (int) $record->lockable_id === 0
            && $this->mayTouch($record)
            && array_key_exists($record->lockable_type, LockPolicy::lockableModules());
    }

    private function clearLock(Lock $record): void
    {
        $clear = app(ClearModuleLock::class);

        (int) $record->lockable_id > 0
            ? $clear->handleRecord(Auth::user(), $record->lockable_type, (int) $record->lockable_id)
            : $clear->handle(Auth::user(), $record->lockable_type, $record->slot);
    }

    private function keyOwners(): array
    {
        $channelIds = Lock::query()->where('slot', Lock::SLOT_OWNER)->select('lockable_id');

        return User::query()
            ->whereIn('id', Channel::withTrashed()->whereIn('id', $channelIds)->select('owner_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function codeFields(): array
    {
        return [
            TextInput::make('code')
                ->label(__('resources/lock/strings.manage.code'))
                ->password()
                ->revealable()
                ->required()
                ->minLength(8)
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (strlen((string) $value) > Lock::MAX_CODE_BYTES) {
                        $fail(__('resources/lock/strings.manage.code_too_long'));
                    }
                })
                ->confirmed(),
            TextInput::make('code_confirmation')
                ->label(__('resources/lock/strings.manage.code_confirm'))
                ->password()
                ->revealable()
                ->required(),
        ];
    }
}
