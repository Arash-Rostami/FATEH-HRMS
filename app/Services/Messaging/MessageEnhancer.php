<?php

namespace App\Services\Messaging;

use App\Services\Ai\AiClient;
use DomainException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class MessageEnhancer
{
    private const SCHEMA = [
        'type' => 'object',
        'properties' => ['answer' => ['type' => 'string']],
        'required' => ['answer'],
        'additionalProperties' => false,
    ];

    private int $spent = 0;

    private bool $called = false;

    private int $subjectId = 0;

    private bool $cut = false;

    private array $module = [];

    private string $settings = '';

    public function __construct(private AiClient $client)
    {
    }

    public function enhance(array $module, int $subjectId, int $userId, string $text): array
    {
        $this->begin($module, $subjectId);

        $stem = $module['stem'];

        $lock = Cache::lock("{$stem}:ai:busy:{$userId}", 3 * ((int) AiClient::setting($this->settings, 'timeout') + (int) AiClient::setting($this->settings, 'connect_timeout')));

        $cooldown = "{$stem}:ai:cooldown:{$userId}";

        if (!$lock->get()) {
            return $this->result('', 'busy', $userId);
        }

        try {
            if (Cache::has($cooldown)) {
                return $this->result('', 'busy', $userId);
            }

            return $this->generate($userId, $text);
        } finally {
            if ($this->called) {
                Cache::put($cooldown, 1, (int) AiClient::setting($this->settings, 'cooldown'));
            }

            $lock->release();
        }
    }

    public function fail(array $module, int $subjectId, int $userId, string $error): array
    {
        $this->begin($module, $subjectId);

        return $this->result('', $error, $userId);
    }

    public function meter(string $quotaModel, int $userId): string
    {
        $meter = $quotaModel::quotaMeter($userId);
        $warn = $meter['pct'] >= 80 && $meter['used'] < $meter['cap']
            ? 'فقط ' . convertToPersian($meter['cap'] - $meter['used']) . ' بار دیگر امروز می‌توانید پیام‌ها را بهبود بدهید.'
            : null;

        return Blade::render('<x-ui.quota-meter label="سهمیهٔ امروز" aria="سهمیهٔ امروز" :used="$used" :cap="$cap" :pct="$pct" :tone="$tone" :warn="$warn"/>', $meter + ['warn' => $warn]);
    }

    private function begin(array $module, int $subjectId): void
    {
        $this->spent = 0;
        $this->called = false;
        $this->cut = false;
        $this->module = $module;
        $this->settings = Str::after($module['config'], 'ai.');
        $this->subjectId = $subjectId;
    }

    private function generate(int $userId, string $text): array
    {
        $model = $this->module['quota'];
        $down = "{$this->module['stem']}:ai:down";

        if (Cache::has($down)) {
            return $this->result('', 'down', $userId);
        }

        $day = now()->format('Y-m-d');

        if ($model::consumeQuota($userId, $day) > $model::quotaCap()) {
            $model::refundQuota($userId, $day);

            return $this->result('', 'quota', $userId);
        }

        $this->called = true;

        try {
            $answer = $this->ask($text);
        } catch (DomainException | RequestException $e) {
            $code = $e instanceof DomainException ? $e->getMessage() : 'unreachable';

            if ($e instanceof RequestException ? ($e->response->status() >= 500 || $e->response->status() === 429) : $code === 'unreachable') {
                Cache::put($down, 1, (int) AiClient::setting($this->settings, 'down_for'));
            }

            $model::refundQuota($userId, $day);

            return $this->result('', in_array($code, ['blocked', 'too_large', 'empty'], true) ? $code : 'unreachable', $userId);
        } catch (Throwable $e) {
            $model::refundQuota($userId, $day);

            throw $e;
        }

        return $this->result($answer, null, $userId);
    }

    private function sanitize(string $text): string
    {
        $text = preg_replace('/[\x{200B}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}\x{061C}\x{2060}\x{00AD}\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        return trim($text);
    }

    private function ask(string $text): string
    {
        $config = $this->module['config'];
        $fence = Str::random(12);

        $messages = [
            ['role' => 'system', 'content' => (string) config('ai.messaging.rules')],
            ['role' => 'user', 'content' => "FENCE: {$fence}\n<<<MESSAGE:{$fence}>>>\n" . preg_replace('/[<>]{3,}/u', '', $text) . "\n<<<END:{$fence}>>>"],
        ];

        $started = microtime(true);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            if ($attempt > 0 && microtime(true) - $started > (int) AiClient::setting($this->settings, 'retry_within')) {
                break;
            }

            try {
                $reply = $this->client->json($messages, self::SCHEMA, $this->module['client'], $this->settings);
            } finally {
                $this->spent += $this->client->lastTokens();
            }

            try {
                $answer = AiClient::parseJson((string) $reply['content'])['answer'] ?? '';
            } catch (JsonException) {
                $answer = '';
            }

            $max = (int) config("{$config}.answer_max");
            $answer = is_string($answer) ? $this->sanitize($answer) : '';

            if (mb_strlen($answer) > $max) {
                $this->cut = true;
                $answer = rtrim(mb_substr($answer, 0, $max - 1)) . '…';
            }

            if ($answer !== '') {
                return $answer;
            }

            if ($reply['content'] !== null) {
                $messages[] = ['role' => 'assistant', 'content' => $reply['content']];
                $messages[] = ['role' => 'user', 'content' => 'Your previous reply had no usable "answer" text. Return only the corrected JSON object.'];
            }
        }

        throw new DomainException('empty');
    }

    private function result(string $answer, ?string $error, int $userId): array
    {
        $config = $this->module['config'];
        $log = "{$this->module['stem']}-ai";
        $context = ['user' => $userId, 'message' => $this->subjectId, 'outcome' => $error ?? 'ok', 'tokens' => $this->spent];

        match ($error) {
            null => Log::info($log, $context),
            'question', 'busy', 'quota', 'down', 'expired', 'long' => Log::debug($log, $context),
            default => Log::warning($log, $context),
        };

        return [
            'answer' => $answer,
            'error' => $error,
            'message' => $error ? config("{$config}.errors.{$error}") : null,
            'soft' => in_array($error, (array) config("{$config}.soft"), true),
            'note' => $error === null ? trim(($this->cut ? trim((string) config("{$config}.cut_note")) . ' ' : '') . trim((string) config("{$config}.applied_note"))) : null,
            'meter' => $this->meter($this->module['quota'], $userId),
        ];
    }
}
