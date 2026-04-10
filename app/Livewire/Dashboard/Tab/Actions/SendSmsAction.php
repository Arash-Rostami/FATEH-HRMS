<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\User;
use App\Services\SmsService;

class SendSmsAction
{
    public function execute(string $userId, SmsService $smsService): array
    {
        $user = User::with('profile')->findOrFail($userId);

        if ($user->sms_number) {
            $smsService->send($user->sms_number, " to be inserted later on ...");
            return [
                'message' => 'پیامک با موفقیت ارسال شد',
                'type' => 'success',
            ];
        } else {
            return [
                'message' => 'شماره تلفن همراه یافت نشد',
                'type' => 'error',
            ];
        }
    }
}
