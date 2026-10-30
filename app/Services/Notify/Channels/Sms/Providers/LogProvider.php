<?php

namespace App\Services\Notify\Channels\Sms\Providers;

use App\Services\Notify\Channels\Sms\SmsProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogProvider implements SmsProvider
{
    public function key(): string
    {
        return 'log';
    }

    public function isConfigured(): bool
    {
        return config('services.sms.provider') === $this->key();
    }

    public function send(string $to, string $body): void
    {
        Log::info('SMS (log provider, nothing delivered).', ['to' => Str::mask($to, '*', 0, -4), 'length' => mb_strlen($body)]);
    }

    public function sendMany(array $to, string $body): array
    {
        Log::info('SMS batch (log provider, nothing delivered).', ['count' => count($to), 'length' => mb_strlen($body)]);

        return array_fill_keys($to, null);
    }
}
