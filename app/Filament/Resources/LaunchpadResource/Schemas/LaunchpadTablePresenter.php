<?php

namespace App\Filament\Resources\LaunchpadResource\Schemas;

use App\Livewire\Dashboard\Launchpad\Actions\ActivateLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Actions\DeactivateLaunchpadAction;
use App\Models\Department;
use App\Models\Launchpad;
use App\Models\User;
use App\Services\Menu\StateService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Collection;

class LaunchpadTablePresenter
{
    public static function audienceCount(): TextColumn
    {
        return TextColumn::make('audience')
            ->label('تعداد مخاطبان')
            ->getStateUsing(fn (Launchpad $record): string => $record->isPublic() ? 'همهٔ کاربران' : convertToPersian((string) count(is_array($record->audience) ? $record->audience : [])))
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function bulkSetActive(bool $active): Closure
    {
        return function (Collection $records) use ($active): void {
            $refused = $records->filter(fn (Launchpad $record): bool => self::setActive($record, $active) !== null)->count();
            $applied = $records->count() - $refused;

            if ($applied > 0) {
                Notification::make()->title(__('resources/general/strings.visibility.' . ($active ? 'activated' : 'deactivated')))->success()->send();
            }

            if ($refused > 0) {
                Notification::make()->title(convertToPersian((string) $refused) . ' برنامه تغییر نکرد؛ مهلت دسترسی آن تمام شده یا در دسترس نیست.')->warning()->send();
            }
        };
    }

    public static function toggleActive(): Closure
    {
        return function (Launchpad $record, mixed $state): bool {
            $active = (bool) $state;
            $error = self::setActive($record, $active);

            if ($error === null) {
                return $active;
            }

            Notification::make()->title($error)->warning()->send();

            return !$active;
        };
    }

    public static function created(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('تاریخ ایجاد')
            ->formatStateUsing(fn ($state) => $state ? toJalaliSmart($state) : '—')
            ->alignRight()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function department(): TextColumn
    {
        return TextColumn::make('department_id')
            ->label('دپارتمان')
            ->formatStateUsing(fn ($state) => Department::getCachedOptions()->get((string) $state) ?? '—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label('دپارتمان')
            ->options(fn () => Department::getCachedOptions()->toArray())
            ->searchable();
    }

    public static function departmentGroup(): Group
    {
        return Group::make('department_id')
            ->label('دپارتمان')
            ->getTitleFromRecordUsing(function (Launchpad $record): string {
                $label = Department::getCachedOptions()->get((string) $record->department_id) ?? '—';
                $used = self::departmentCounts()[$record->department_id] ?? 0;

                return $label . ' — ' . convertToPersian((string) $used) . ' / ' . convertToPersian((string) config('launchpad.max_per_department'));
            })
            ->collapsible();
    }

    public static function expiry(): TextColumn
    {
        return TextColumn::make('expire_at')
            ->label('آخرین روز نگهداری')
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function kind(): TextColumn
    {
        return TextColumn::make('kind')
            ->label('نوع برنامه')
            ->formatStateUsing(fn (string $state): string => match ($state) {
                'static' => 'بارگذاری فایل',
                'link' => 'میان‌بر به سامانه',
                default => $state,
            })
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function owner(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label('مالک')
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function refreshed(): TextColumn
    {
        return TextColumn::make('refresh')
            ->label('آخرین دریافت اطلاعات')
            ->badge()
            ->placeholder('—')
            ->getStateUsing(fn (Launchpad $record): ?string => $record->refresh_due_at === null ? null : ($record->settings['refresh']['status'] ?? 'pending'))
            ->formatStateUsing(fn (string $state): string => config("launchpad.refresh.statuses.{$state}.label") ?? config('launchpad.refresh.statuses.pending.label'))
            ->color(fn (string $state): string => config("launchpad.refresh.statuses.{$state}.color") ?? 'gray')
            ->description(fn (Launchpad $record): ?string => isset($record->settings['refresh']['at']) ? toJalaliSmart($record->settings['refresh']['at']) : null)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function reassignOwner(): Action
    {
        return Action::make('reassignOwner')
            ->label('تغییر مالک')
            ->icon('heroicon-o-user-plus')
            ->iconButton()
            ->tooltip('تغییر مالک برنامه')
            ->modalHeading('تغییر مالک برنامه')
            ->schema([
                Select::make('user_id')
                    ->label('مالک جدید')
                    ->options(fn () => User::getCachedActiveOptions()->toArray())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, Launchpad $record): void {
                $userId = (int) ($data['user_id'] ?? 0);

                if ($userId <= 0 || !User::getCachedActiveOptions()->has($userId)) {
                    return;
                }

                $audience = array_values(array_filter(
                    array_map('intval', is_array($record->audience) ? $record->audience : []),
                    fn (int $id): bool => $id !== $userId,
                ));

                $record->update(['user_id' => $userId, 'audience' => $audience]);
                StateService::flush();

                Notification::make()
                    ->title('مالک برنامه تغییر کرد.')
                    ->success()
                    ->send();
            });
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label('وضعیت')
            ->badge()
            ->getStateUsing(fn (Launchpad $record): string => $record->status())
            ->formatStateUsing(fn (string $state): string => match ($state) {
                'inactive' => 'غیرفعال',
                'expired' => 'مهلت دسترسی تمام شده',
                'scheduled' => 'هنوز زمان دسترسی نرسیده',
                'soon' => 'نزدیک پایان نگهداری',
                default => 'فعال',
            })
            ->color(fn (string $state): string => match ($state) {
                'inactive' => 'gray',
                'expired' => 'danger',
                'scheduled' => 'info',
                'soon' => 'warning',
                default => 'success',
            })
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label('نام برنامه')
            ->searchable()
            ->limit(40)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    private static function setActive(Launchpad $record, bool $active): ?string
    {
        $user = auth()->user();
        $missing = 'این برنامه در دسترس نیست.';

        if (!$active) {
            return app(DeactivateLaunchpadAction::class)->execute((int) $record->id, $user) ? null : $missing;
        }

        return match (app(ActivateLaunchpadAction::class)->execute((int) $record->id, $user)) {
            'ok' => null,
            'expired' => 'مهلت دسترسی به برنامه تمام شده است.',
            default => $missing,
        };
    }

    private static function departmentCounts(): array
    {
        $bag = request()->attributes;

        if (! $bag->has('launchpad.department_counts')) {
            $bag->set('launchpad.department_counts', Launchpad::query()
                ->selectRaw('department_id, COUNT(*) as total')
                ->whereNotNull('department_id')
                ->groupBy('department_id')
                ->pluck('total', 'department_id')
                ->all());
        }

        return $bag->get('launchpad.department_counts');
    }
}
