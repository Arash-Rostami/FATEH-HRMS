<?php

namespace App\Services\Notify\Channels;

use App\Models\User;
use App\Services\Notify\Channels\Sms\SmsProviderRegistry;
use App\Services\Notify\Contracts\NotifyChannel;
use App\Services\Notify\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SmsChannel implements NotifyChannel
{
    private const MOBILE_PATTERN = '/^(\+98|0)?9\d{9}$/';

    private const BIDI_PATTERN = '/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';

    public function key(): string
    {
        return 'sms';
    }

    public function label(): string
    {
        return 'پیامک';
    }

    public function isConfigured(): bool
    {
        return SmsProviderRegistry::active()->isConfigured();
    }

    public function addressFor(User $user): ?string
    {
        return $user->sms_number ?: null;
    }

    public function accepts(string $contact): bool
    {
        return preg_match(self::MOBILE_PATTERN, $contact) === 1;
    }

    public function normalize(string $address): string
    {
        return '0'.substr(preg_replace('/\D+/', '', $address), -10);
    }

    public function send(Message $message, string $address): bool
    {
        $this->deliver($address, $this->capped($message));

        return true;
    }

    public function sendMany(Message $message, array $addresses): array
    {
        return SmsProviderRegistry::active()->sendMany($addresses, preg_replace(self::BIDI_PATTERN, '', $this->capped($message)));
    }

    private function capped(Message $message): string
    {
        return mb_strimwidth($message->text(), 0, (int) config('notify.sms_max'), '…');
    }

    public function text(string $to, string $body): bool
    {
        try {
            $this->deliver($to, $body);

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to send SMS.', ['to' => Str::mask($to, '*', 0, -4), 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function deliver(string $to, string $body): void
    {
        SmsProviderRegistry::active()->send($to, preg_replace(self::BIDI_PATTERN, '', $body));
    }
}
