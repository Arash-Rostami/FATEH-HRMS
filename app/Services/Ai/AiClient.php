<?php

namespace App\Services\Ai;

use DomainException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

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

    public function json(array $messages, array $schema, string $feature, ?string $settings = null): array
    {
        $this->tokens = 0;
        $settings ??= $feature;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'apikey ' . (string) config('ai.gateway.key'),
            ])->timeout((int) self::setting($settings, 'timeout'))
                ->connectTimeout((int) self::setting($settings, 'connect_timeout'))
                ->acceptJson()
                ->post(rtrim((string) config('ai.gateway.url'), '/') . '/chat/completions', [
                    'model' => (string) self::setting($settings, 'model'),
                    'messages' => $messages,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => ['name' => $feature, 'strict' => true, 'schema' => $schema],
                    ],
                    'max_tokens' => (int) self::setting($settings, 'max_output'),
                    'temperature' => (float) (config("ai.{$settings}.temperature") ?? 0.2),
                ]);
            $response->throw();
        } catch (GuzzleException | ConnectionException) {
            throw new DomainException('unreachable');
        }

        $json = $response->json();

        return $this->read(is_array($json) ? $json : []);
    }

    public static function parseJson(string $content): array
    {
        try {
            $strict = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return is_array($strict) ? $strict : throw new JsonException('not an object');
        } catch (JsonException $e) {
            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            if ($start === false || $end === false || $end <= $start) {
                throw $e;
            }
            $decoded = json_decode(substr($content, $start, $end - $start + 1), true, 512);

            return is_array($decoded) ? $decoded : throw $e;
        }
    }

    public static function cleanText(string $text, bool $multiline, int $max, bool $bullets = false): string
    {
        $markers = $bullets ? '#{1,6}' : '#{1,6}|[*\-•]|\d{1,2}[.)]';
        $text = preg_replace(['/[\x{200B}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}\x{061C}\x{2060}\x{00AD}\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '/^[ \t]*(?:' . $markers . ')[ \t]+/mu', '/\*\*|`+/u'], '', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = $bullets ? preg_replace('/ ?•[ \t]*/u', "\n• ", $text) : $text;
        $text = $multiline ? preg_replace('/ ?\R\s*/u', "\n", $text) : preg_replace('/\s+/u', ' ', $text);

        return mb_substr(convertToPersian(trim($text)) ?? '', 0, $max);
    }

    public static function setting(string $feature, string $key): mixed
    {
        return config("ai.{$feature}.{$key}") ?? config("ai.gateway.{$key}")
            ?? throw new RuntimeException("AI setting missing: ai.{$feature}.{$key} and ai.gateway.{$key} are both unset; run `php artisan config:clear` and check config/ai.php.");
    }

    public static function cooldownActive(string $feature, int $userId): bool
    {
        return Cache::has("{$feature}:ai:cooldown:{$userId}");
    }

    public static function startCooldown(string $feature, int $userId): void
    {
        Cache::put("{$feature}:ai:cooldown:{$userId}", 1, (int) self::setting($feature, 'cooldown'));
    }

    private function read(array $json): array
    {
        $content = data_get($json, 'choices.0.message.content');
        $finish = data_get($json, 'choices.0.finish_reason');
        $finish = is_string($finish) ? strtolower($finish) : $finish;
        $tokens = $this->tokens = (int) data_get($json, 'usage.total_tokens', 0);
        if ($finish === 'length') {
            throw new DomainException('too_large');
        }
        if ($finish === 'content_filter') {
            throw new DomainException('blocked');
        }
        if (!is_string($content) || trim($content) === '' || (is_string($finish) && $finish !== 'stop')) {
            return ['content' => null, 'tokens' => $tokens];
        }

        return ['content' => $content, 'tokens' => $tokens];
    }
}
