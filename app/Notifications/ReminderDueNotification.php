<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Reminder $reminder)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject('یادآوری: ' . $this->reminder->title)
            ->line('یادآوری «' . $this->reminder->title . '» سررسید شده است.');

        if ($this->reminder->notes) {
            $mail->line($this->reminder->notes);
        }

        $url = $this->reminder->hostUrl();

        if ($url) {
            $mail->action('مشاهده', $url);
        }

        return $mail;
    }
}
