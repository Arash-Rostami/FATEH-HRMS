<?php

namespace App\Traits\Livewire;

use App\Livewire\Dashboard\Tab\Actions\SendSmsAction;
use App\Models\NotifyLog;
use App\Services\Notify\Channels\SmsChannel;
use Illuminate\Support\Facades\Auth;

trait SendsSms
{
    public bool $showSmsModal = false;
    public ?int $smsUserId = null;
    public string $smsText = '';
    public string $smsType = '';
    public string $smsKey = '';

    public function submitSms(SendSmsAction $action, SmsChannel $sms): void
    {
        if (tenantOff('notify:sms')) {
            return;
        }

        $this->validate(
            ['smsText' => 'required|string|max:140'],
            ['smsText.required' => 'متن پیام را بنویسید.', 'smsText.max' => 'متن پیام حداکثر ۱۴۰ نویسه است.'],
        );

        if ($this->sendSms((string) $this->smsUserId, $action, $sms, $this->smsText)) {
            $this->showSmsModal = false;
            $this->smsText = '';
        }
    }

    public function sendSms(string $userId, SendSmsAction $action, SmsChannel $sms, ?string $text = null): bool
    {
        if (tenantOff('notify:sms')) {
            return false;
        }

        $context = $this->smsContext((int) $userId);
        if ($context === null) {
            $this->dispatch('toast', message: 'ارسال پیامک برای این مناسبت ممکن نیست.', type: 'error');
            return false;
        }

        $result = $action->execute($userId, $sms, $text, $context['source'], $context['key']);
        $this->dispatch('toast', message: $result['message'], type: $result['type']);

        if ($result['type'] === 'success') {
            $this->dispatch('sms-sent', key: $context['key'], senderId: (int) Auth::id(), source: $context['source']);
        }

        return $result['type'] === 'success';
    }

    protected function smsContext(int $userId): ?array
    {
        return ['source' => NotifyLog::SOURCE_STATUS, 'key' => null];
    }
}
