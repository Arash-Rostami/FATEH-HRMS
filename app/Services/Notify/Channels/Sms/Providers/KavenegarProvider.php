<?php

namespace App\Services\Notify\Channels\Sms\Providers;

use App\Services\Notify\Channels\Sms\SmsProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class KavenegarProvider implements SmsProvider
{
    private const FAILED = [
        6 => 'ارسال پیامک ناموفق بود.',
        11 => 'پیامک به گیرنده نرسید.',
        13 => 'ارسال پیامک لغو شد.',
        14 => 'شمارهٔ گیرنده مسدود است.',
        100 => 'شمارهٔ گیرنده نامعتبر است.',
    ];

    public function key(): string
    {
        return 'kavenegar';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.kavenegar.key'));
    }

    public function send(string $to, string $body): void
    {
        if ($error = $this->sendMany([$to], $body)[$to] ?? null) {
            throw new RuntimeException($error);
        }
    }

    public function sendMany(array $to, string $body): array
    {
        try {
            $entries = $this->request($to, $body);
        } catch (RuntimeException $e) {
            return array_fill_keys($to, $e->getMessage());
        }

        $failed = [];

        foreach ($entries as $entry) {
            if (isset(self::FAILED[$status = (int) ($entry['status'] ?? 0)])) {
                $failed[$this->tail((string) ($entry['receptor'] ?? ''))] = self::FAILED[$status];
            }
        }

        return array_combine($to, array_map(fn (string $number) => $failed[$this->tail($number)] ?? null, $to));
    }

    private function tail(string $number): string
    {
        return substr(preg_replace('/\D+/', '', $number), -10);
    }

    private function request(array $to, string $body): array
    {
        if (! $key = config('services.kavenegar.key')) {
            throw new RuntimeException('سرویس پیامک پیکربندی نشده است.');
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post("https://api.kavenegar.com/v1/{$key}/sms/send.json", array_filter([
                    'receptor' => implode(',', $to),
                    'message' => $body,
                    'sender' => config('services.kavenegar.sender'),
                ]));
        } catch (Throwable $e) {
            Log::warning('SMS gateway request failed.', ['provider' => $this->key(), 'error' => str_replace($key, '***', $e->getMessage())]);

            throw new RuntimeException('اتصال به سرویس پیامک برقرار نشد.');
        }

        if (($status = (int) $response->json('return.status')) !== 200) {
            throw new RuntimeException(
                "کاوه‌نگار {$status}: ".($response->json('return.message') ?: 'خطای نامشخص')
            );
        }

        return (array) $response->json('entries', []);
    }
}
