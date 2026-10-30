<?php

namespace App\Jobs;

use App\Models\Reminder;
use App\Services\Notify\Message;
use App\Services\Notify\NotifyRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SendReminderSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(public int $reminderId) {}

    public function handle(): void
    {
        $reminder = Reminder::with('user.profile:id,user_id,cellphone')->find($this->reminderId);
        $channel = NotifyRegistry::channel('sms');
        $number = $reminder?->user?->sms_number;

        if ($reminder === null || $channel === null || ! $channel->isConfigured() || blank($number)) {
            return;
        }

        $channel->send(new Message(
            'یادآوری «'.$reminder->title.'» سررسید شده است.',
            array_values(array_filter([$reminder->notes])),
            'reminder',
            __('resources/reminder/strings.plural_label'),
            'سررسید',
        ), $number);
    }
}
