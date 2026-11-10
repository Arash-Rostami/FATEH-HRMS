<?php

namespace App\Services\Ai;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AiClient
{
    private int $tokens = 0;

    public static function isConfigured(): bool
    {
        return filled(config('ai.gateway.key')) && filled(config('ai.gateway.url'));
    }

    public function lastTokens(): int
    {
        return $this->tokens;
    }

    public function json(array $messages, array $schema, string $feature): array
    {
        $this->tokens = 0;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'apikey ' . (string) config('ai.gateway.key'),
            ])->timeout((int) self::setting($feature, 'timeout'))
                ->connectTimeout((int) self::setting($feature, 'connect_timeout'))
                ->acceptJson()
                ->post(rtrim((string) config('ai.gateway.url'), '/') . '/chat/completions', [
                    'model' => (string) self::setting($feature, 'model'),
                    'messages' => $messages,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => ['name' => $feature, 'strict' => true, 'schema' => $schema],
                    ],
                    'max_tokens' => (int) self::setting($feature, 'max_output'),
                    'temperature' => (float) (config("ai.{$feature}.temperature") ?? 0.2),
                ]);
            $response->throw();
        } catch (GuzzleException | ConnectionException) {
            throw new \DomainException('unreachable');
        }

        $json = $response->json();

        return $this->read(is_array($json) ? $json : []);
    }

    public static function parseJson(string $content): array
    {
        try {
            $strict = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return is_array($strict) ? $strict : throw new \JsonException('not an object');
        } catch (\JsonException $e) {
            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            if ($start === false || $end === false || $end <= $start) {
                throw $e;
            }
            $decoded = json_decode(substr($content, $start, $end - $start + 1), true, 512);

            return is_array($decoded) ? $decoded : throw $e;
        }
    }

    public static function setting(string $feature, string $key): mixed
    {
        return config("ai.{$feature}.{$key}") ?? config("ai.gateway.{$key}")
            ?? throw new \RuntimeException("AI setting missing: ai.{$feature}.{$key} and ai.gateway.{$key} are both unset; run `php artisan config:clear` and check config/ai.php.");
    }

    private function read(array $json): array
    {
        $content = data_get($json, 'choices.0.message.content');
        $finish = data_get($json, 'choices.0.finish_reason');
        $finish = is_string($finish) ? strtolower($finish) : $finish;
        $tokens = $this->tokens = (int) data_get($json, 'usage.total_tokens', 0);
        if ($finish === 'length') {
            throw new \DomainException('too_large');
        }
        if ($finish === 'content_filter') {
            throw new \DomainException('blocked');
        }
        if (!is_string($content) || trim($content) === '' || (is_string($finish) && $finish !== 'stop')) {
            return ['content' => null, 'tokens' => $tokens];
        }

        return ['content' => $content, 'tokens' => $tokens];
    }
}
