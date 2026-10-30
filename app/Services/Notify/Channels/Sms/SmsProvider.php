<?php

namespace App\Services\Notify\Channels\Sms;

interface SmsProvider
{
    public function key(): string;

    public function isConfigured(): bool;

    public function send(string $to, string $body): void;

    public function sendMany(array $to, string $body): array;
}
