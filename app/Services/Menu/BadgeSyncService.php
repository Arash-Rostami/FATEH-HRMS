<?php

namespace App\Services\Menu;

use App\Services\Menu\Contracts\MenuBadge;
use Filament\Actions\Action;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class BadgeSyncService
{
    public function sync($user, MenuBadge $indicator, bool $isActive): void
    {
        if (!$user) return;

        $clearedBadges = $user->extra['cleared_badges'] ?? [];
        if (in_array($indicator->getKey(), $clearedBadges)) {
            return;
        }

        $query = $user->notifications()
            ->where('type', FilamentDatabaseNotification::class)
            ->where('data->menu_key', $indicator->getKey());

        if ($isActive) {
            if (!$query->exists()) {
                $notificationId = (string)Str::uuid();

                $message = Notification::make()
                    ->id($notificationId)
                    ->title($indicator->getTitle())
                    ->body($indicator->getBody())
                    ->warning()
                    ->persistent()
                    ->actions([
                        Action::make('read')
                            ->label('خواندم')
                            ->markAsRead()
                            ->button(),
                        Action::make('clear')
                            ->label('پاک کردن')
                            ->button()
                            ->color('danger')
                            ->dispatch('clearBadge', ['notificationId' => $notificationId, 'menuKey' => $indicator->getKey()]),
                    ])
                    ->getDatabaseMessage();

                $user->notifications()->create([
                    'id' => $notificationId,
                    'type' => FilamentDatabaseNotification::class,
                    'data' => [
                        ...$message,
                        'menu_key' => $indicator->getKey(),
                    ],
                ]);
            }

            return;
        }

        $query->delete();
    }
}
