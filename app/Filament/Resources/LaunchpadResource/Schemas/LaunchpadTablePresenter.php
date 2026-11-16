<?php

namespace App\Filament\Resources\LaunchpadResource\Schemas;

use App\Models\Department;
use App\Models\Launchpad;
use App\Models\User;
use App\Services\Menu\StateService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;

class LaunchpadTablePresenter
{
    public static function audienceCount(): TextColumn
    {
        return TextColumn::make('audience')
            ->label('تعداد مخاطبان')
            ->getStateUsing(fn (Launchpad $record): string => convertToPersian((string) count(is_array($record->audience) ? $record->audience : [])))
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: false);
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
            ->label('واحد')
            ->formatStateUsing(fn ($state) => Department::getCachedOptions()->get((string) $state) ?? '—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label('واحد')
            ->options(fn () => Department::getCachedOptions()->toArray())
            ->searchable();
    }

    public static function departmentGroup(): Group
    {
        return Group::make('department_id')
            ->label('واحد')
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
            ->label('تاریخ انقضا')
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function kind(): TextColumn
    {
        return TextColumn::make('kind')
            ->label('نوع')
            ->formatStateUsing(fn (string $state): string => match ($state) {
                'static' => 'استاتیک',
                'link' => 'پیوند',
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

    public static function reassignOwner(): Action
    {
        return Action::make('reassignOwner')
            ->label('تغییر مالک')
            ->icon('heroicon-o-user-plus')
            ->iconButton()
            ->tooltip('تغییر مالک پیشخوان')
            ->modalHeading('تغییر مالک پیشخوان')
            ->schema([
                Select::make('user_id')
                    ->label('مالک جدید')
                    ->options(fn () => User::getCachedActiveOptions()->toArray())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, Launchpad $record): void {
                $userId = (int) ($data['user_id'] ?? 0);

                if ($userId <= 0) {
                    return;
                }

                $audience = array_values(array_filter(
                    array_map('intval', is_array($record->audience) ? $record->audience : []),
                    fn (int $id): bool => $id !== $userId,
                ));

                $record->update(['user_id' => $userId, 'audience' => $audience]);
                StateService::flush();

                Notification::make()
                    ->title('مالک پیشخوان تغییر کرد.')
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
                'expired' => 'منقضی',
                'scheduled' => 'زمان‌بندی‌شده',
                'soon' => 'نزدیک انقضا',
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
            ->label('عنوان')
            ->searchable()
            ->limit(40)
            ->toggleable(isToggledHiddenByDefault: false);
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
