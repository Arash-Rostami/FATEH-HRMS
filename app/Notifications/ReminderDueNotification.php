<?php

namespace App\Notifications;

use App\Mail\NotifyMail;
use App\Models\Reminder;
use App\Services\Notify\Message;
use App\Services\Notify\MessageBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReminderDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Reminder $reminder)
    {
    }

    public function via($notifiable): array
    {
        return blank($notifiable->email) ? [] : ['mail'];
    }

    public function toMail($notifiable): NotifyMail
    {
        $message = new Message(
            MessageBuilder::isolate('یادآوری: '.$this->reminder->title),
            array_values(array_filter([
                'یادآوری «'.$this->reminder->title.'» سررسید شده است.',
                $this->reminder->notes,
            ])),
            'reminder',
            __('resources/reminder/strings.plural_label'),
            'سررسید',
            'styled',
            $this->reminder->hostUrlIfAvailable(),
        );

        return (new NotifyMail($message))->to($notifiable->email);
    }
}
