<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ChannelResource;
use App\Models\Channel;
use App\Models\User;
use App\Services\Lock\UnlockModule;
use App\Support\LockPolicy;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UnlockModules extends Page
{
    protected static ?string $slug = 'unlock-modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-open';

    public static function canAccess(): bool
    {
        return static::lockedOptions() !== [];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('resources/lock/strings.unlock.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('resources/lock/strings.unlock.title');
    }

    public function boot(): void
    {
        abort_unless(static::canAccess(), 404);
    }

    public function mount(): void
    {
        $option = 'channel:' . (int) request()->query('record');

        if (array_key_exists($option, static::lockedOptions())) {
            Notification::make()->title(__('resources/lock/strings.unlock.record_locked'))->warning()->send();
        }

        $this->mountAction('unlock', ['module' => $option]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/lock/strings.unlock.title'))->description(__('resources/lock/strings.unlock.hint')),
        ]);
    }

    public function unlockAction(): Action
    {
        return Action::make('unlock')
            ->label(__('resources/lock/strings.unlock.action'))
            ->icon('heroicon-o-lock-open')
            ->fillForm(fn (array $arguments): array => ['module' => array_key_exists($arguments['module'] ?? '', static::lockedOptions()) ? $arguments['module'] : null])
            ->schema([
                Select::make('module')
                    ->label(__('resources/lock/strings.unlock.module'))
                    ->options(fn (): array => static::lockedOptions())
                    ->required(),
                TextInput::make('code')
                    ->label(__('resources/lock/strings.unlock.code'))
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->action(function (array $data, Action $action): void {
                [$module, $recordId] = array_pad(explode(':', $data['module'], 2), 2, 0);

                try {
                    $unlocked = app(UnlockModule::class)->handle(Auth::user(), $module, $data['code'], (int) $recordId);
                } catch (HttpException $e) {
                    abort_unless($e->getStatusCode() === 429, $e->getStatusCode());

                    Notification::make()->title(__('resources/lock/strings.unlock.throttled'))->danger()->send();
                    $action->halt();
                }

                if (!$unlocked) {
                    Notification::make()->title(__('resources/lock/strings.unlock.failed'))->danger()->send();
                    $action->halt();
                }

                Notification::make()->title(__($recordId > 0 ? 'resources/lock/strings.unlock.record_success' : 'resources/lock/strings.unlock.success'))->success()->send();
                $this->redirect(static::destination($module, (int) $recordId));
            });
    }

    protected function getHeaderActions(): array
    {
        return [$this->unlockAction()];
    }

    private static function destination(string $module, int $recordId): string
    {
        if ($recordId === 0) {
            return Filament::getUrl();
        }

        $channel = Channel::withTrashed()->find($recordId);

        return $channel !== null && ChannelResource::canEdit($channel)
            ? ChannelResource::getUrl('edit', ['record' => $recordId])
            : ChannelResource::getUrl('index');
    }

    private static function lockedOptions(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $modules = LockPolicy::lockedKeys() === [] ? [] : collect(LockPolicy::lockableModules())
            ->filter(fn (string $label, string $module): bool => LockPolicy::blocks($module) && $user->permits($module, 'view'))
            ->all();

        return $modules + static::lockedChannels($user);
    }

    private static function lockedChannels(User $user): array
    {
        $ids = LockPolicy::lockedRecordIds('channel');

        if ($ids === [] || LockPolicy::blocks('channel') || !$user->permits('channel', 'view')) {
            return [];
        }

        return Channel::withTrashed()->whereKey($ids)->orderBy('name')->get(['id', 'name', 'owner_id'])
            ->filter(fn (Channel $channel): bool => LockPolicy::blocksRecord('channel', $channel))
            ->mapWithKeys(fn (Channel $channel): array => ["channel:{$channel->id}" => __('resources/lock/strings.unlock.record_label', ['name' => $channel->name])])
            ->all();
    }
}
