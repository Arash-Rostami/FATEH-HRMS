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
        if (!$user) {
            return;
        }

        $query = $user->notifications()
            ->where('type', FilamentDatabaseNotification::class)
            ->where('data->menu_key', $indicator->getKey());

        if (!$isActive) {
            $query->delete();
            return;
        }

        if ($query->exists()) {
            return;
        }

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => FilamentDatabaseNotification::class,
            'data' => [
                ...Notification::make()
                    ->title($indicator->getTitle())
                    ->body($indicator->getBody())
                    ->warning()
                    ->persistent()
                    ->actions([
                        Action::make('read')
                            ->label('حذف اعلان')
                            ->color('primary')
                            ->markAsRead()
                            ->button(),
                    ])
                    ->getDatabaseMessage(),
                'menu_key' => $indicator->getKey(),
            ],
        ]);
    }
}