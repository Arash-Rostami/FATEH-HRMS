<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $recipient, string $message): bool
    {
        try {
            // Placeholder for SMS Gateway integration
            // e.g., Kavenegar::send($recipient, $message);

            Log::info("SMS sent to {$recipient}: {$message}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send SMS to {$recipient}: {$e->getMessage()}");
            return false;
        }
    }
}
