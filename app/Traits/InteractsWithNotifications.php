<?php

namespace App\Traits;

use Filament\Actions\Action;
use Filament\Notifications\Notification;

trait InteractsWithNotifications
{
    /**
     * Send a notification with an action that dispatches a Livewire event.
     *
     * @param string $title
     * @param string $body
     * @param string $eventName The Livewire event to dispatch
     * @param array $eventData Data to send with the event
     */
    public function notifyWithAction(string $title, string $body, string $eventName, array $eventData = []): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->actions([
                Action::make('view')
                    ->button()
                    ->dispatch($eventName, $eventData)
                    ->close(),
            ])
            ->sendToDatabase(auth()->user());
    }
}
