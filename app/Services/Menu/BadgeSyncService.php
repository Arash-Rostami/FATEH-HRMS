<?php

namespace App\Services\Menu;

use App\Services\Menu\Contracts\MenuBadge;
use Filament\Actions\Action;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class BadgeSyncService
{
    /**
     * @param MenuBadge[] $indicators
     */
    public function syncBatch($user, array $indicators, array $state): void
    {
        if (!$user || !$indicators) {
            return;
        }

        $keys = array_map(fn (MenuBadge $i) => $i->getKey(), $indicators);

        $existingByKey = $user->notifications()
            ->where('type', FilamentDatabaseNotification::class)
            ->whereIn('data->menu_key', $keys)
            ->get(['data'])
            ->mapWithKeys(fn ($n) => [($n->data['menu_key'] ?? null) => true])
            ->toArray();

        $inactiveKeys = [];
        $toInsert = [];
        $now = now();

        foreach ($indicators as $indicator) {
            try {
                $key = $indicator->getKey();
                $isActive = $state[$key] ?? false;

                if (!$isActive) {
                    $inactiveKeys[] = $key;
                    continue;
                }

                if (isset($existingByKey[$key])) {
                    continue;
                }

                $toInsert[] = [
                    'id' => (string) Str::uuid(),
                    'type' => FilamentDatabaseNotification::class,
                    'notifiable_type' => $user->getMorphClass(),
                    'notifiable_id' => $user->getKey(),
                    'data' => json_encode([
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
                        'menu_key' => $key,
                    ]),
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($inactiveKeys) {
            $user->notifications()
                ->where('type', FilamentDatabaseNotification::class)
                ->whereIn('data->menu_key', $inactiveKeys)
                ->delete();
        }

        if ($toInsert) {
            $user->notifications()->insert($toInsert);
        }
    }
}