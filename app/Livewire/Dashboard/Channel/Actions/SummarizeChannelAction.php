<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Services\Ai\AiClient;
use DomainException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class SummarizeChannelAction
{
    private const SCHEMA = [
        'type' => 'object',
        'properties' => ['answer' => ['type' => 'string']],
        'required' => ['answer'],
        'additionalProperties' => false,
    ];

    private int $spent = 0;

    private bool $called = false;

    private int $channelId = 0;

    private int $pinnedId = 0;

    private bool $cut = false;

    public function __construct(private AiClient $client)
    {
    }

    public function execute(int $channelId, int $userId, string $mode, string $question): array
    {
        abort_unless(channelAiOn(), 404);

        $this->spent = 0;

        $config = config("ai.channel.modes.{$mode}");

        abort_unless(is_array($config), 422);

        $user = auth()->user();

        abort_unless($user?->isActive() && (int) $user->id === $userId, 403);

        $this->channelId = $channelId;

        $channel = Channel::withoutTrashed()
            ->whereKey($channelId)
            ->whereHas('memberUsers', fn ($q) => $q->where('users.id', $userId))
            ->first(['id', 'pinned_message_id']);

        abort_unless($channel, 403);

        $this->pinnedId = (int) $channel->pinned_message_id;

        if ($mode === 'ask' && $question === '') {
            return $this->result('', 'question', $userId);
        }

        $lock = Cache::lock("channel:ai:busy:{$userId}", 3 * ((int) AiClient::setting('channel', 'timeout') + (int) AiClient::setting('channel', 'connect_timeout')));

        $cooldown = "channel:ai:cooldown:{$userId}";

        if (!$lock->get()) {
            return $this->result('', 'busy', $userId);
        }

        $this->called = false;
        $this->cut = false;

        try {
            if (Cache::has($cooldown)) {
                return $this->result('', 'busy', $userId);
            }

            return $this->generate($channelId, $userId, $config, $mode === 'ask' ? $question : '');
        } finally {
            if ($this->called) {
                Cache::put($cooldown, 1, (int) AiClient::setting('channel', 'cooldown'));
            }

            $lock->release();
        }
    }

    private function generate(int $channelId, int $userId, array $config, string $question): array
    {
        if (Cache::has('channel:ai:down')) {
            return $this->result('', 'down', $userId);
        }

        [$lines, $truncated] = $this->lines($channelId, (int) $config['hours']);

        if ($lines === '') {
            return $this->result('', 'none', $userId);
        }

        $day = now()->format('Y-m-d');

        if (Channel::consumeQuota($userId, $day) > Channel::quotaCap()) {
            Channel::refundQuota($userId, $day);

            return $this->result('', 'quota', $userId);
        }

        $this->called = true;

        try {
            $answer = $this->ask((string) $config['task'], $question, $lines);
        } catch (DomainException | RequestException $e) {
            $code = $e instanceof DomainException ? $e->getMessage() : 'unreachable';

            if ($e instanceof RequestException ? ($e->response->status() >= 500 || $e->response->status() === 429) : $code === 'unreachable') {
                Cache::put('channel:ai:down', 1, (int) AiClient::setting('channel', 'down_for'));
            }

            Channel::refundQuota($userId, $day);

            return $this->result('', in_array($code, ['blocked', 'too_large', 'empty'], true) ? $code : 'unreachable', $userId);
        } catch (Throwable $e) {
            Channel::refundQuota($userId, $day);

            throw $e;
        }

        return $this->result($answer, null, $userId, $truncated);
    }

    private function lines(int $channelId, int $hours): array
    {
        $cap = (int) config('ai.channel.max_chars');
        $max = (int) config('ai.channel.max_messages');
        $lines = [];
        $size = 0;

        $texts = fn () => ChannelMessage::withoutTrashed()
            ->leftJoin('users', 'users.id', '=', 'channel_messages.sender_id')
            ->where('channel_messages.channel_id', $channelId)
            ->whereNotNull('channel_messages.body')
            ->where('channel_messages.body', '<>', '');
        $columns = ['channel_messages.id', 'channel_messages.reply_to_id', 'channel_messages.body', 'channel_messages.created_at', 'users.name'];

        $since = now()->subHours($hours);

        $rows = $texts()
            ->where('channel_messages.created_at', '>=', $since)
            ->latest('channel_messages.id')
            ->limit($max + 1)
            ->get($columns);

        $truncated = $rows->count() > $max;

        $pin = $this->pinnedId ? $texts()->where('channel_messages.id', $this->pinnedId)->first($columns) : null;
        $pinLine = $pin ? $this->format($pin, $cap, $pin->created_at->lt($since)) : null;
        $budget = $cap - mb_strlen((string) $pinLine);

        foreach ($rows->take($max) as $row) {
            if ($row->id === $this->pinnedId) {
                continue;
            }

            $line = $this->format($row, $cap);

            if ($line === null) {
                continue;
            }

            $size += mb_strlen($line);

            if ($size > $budget) {
                $truncated = true;

                break;
            }

            $lines[$this->sortKey($row)] = $line;
        }

        if ($pinLine !== null) {
            $lines[$this->sortKey($pin)] = $pinLine;
        }

        ksort($lines);

        return [implode("\n", $lines), $truncated];
    }

    private function sortKey(ChannelMessage $row): string
    {
        return sprintf('%011d-%012d', $row->created_at->getTimestamp(), $row->id);
    }

    private function format(ChannelMessage $row, int $cap, bool $outside = false): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $row->body));

        if ($text === '') {
            return null;
        }

        return mb_substr(preg_replace('/[<>]{3,}/u', '', '#' . $row->id . ($row->id === $this->pinnedId ? ($outside ? ' [PINNED, OUTSIDE PERIOD]' : ' [PINNED]') : '') . ($row->reply_to_id ? " (↪#{$row->reply_to_id})" : '') . ' [' . toJalali($row->created_at, 'm/d H:i') . '] ' . ($row->name ?? '—') . ': ' . $text), 0, $cap);
    }

    private function ask(string $task, string $question, string $lines): string
    {
        $fence = Str::random(12);
        $asked = $question !== '' ? 'QUESTION: ' . preg_replace('/[<>]{3,}/u', '', $question) . "\n" : '';

        $messages = [
            ['role' => 'system', 'content' => (string) config('ai.channel.rules')],
            ['role' => 'user', 'content' => "FENCE: {$fence}\nTASK: {$task}\n<<<MESSAGES:{$fence}>>>\n{$asked}{$lines}\n<<<END:{$fence}>>>"],
        ];

        $started = microtime(true);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            if ($attempt > 0 && microtime(true) - $started > (int) AiClient::setting('channel', 'retry_within')) {
                break;
            }

            try {
                $reply = $this->client->json($messages, self::SCHEMA, 'channel');
            } finally {
                $this->spent += $this->client->lastTokens();
            }

            try {
                $answer = AiClient::parseJson((string) $reply['content'])['answer'] ?? '';
            } catch (JsonException) {
                $answer = '';
            }

            $max = (int) config('ai.channel.answer_max');
            $answer = is_string($answer) ? AiClient::cleanText($answer, true, $max + 1, true) : '';

            if (mb_strlen($answer) > $max) {
                $this->cut = true;
                $answer = rtrim(mb_substr($answer, 0, $max)) . '…';
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

    private function result(string $answer, ?string $error, int $userId, bool $truncated = false): array
    {
        $context = ['user' => $userId, 'channel' => $this->channelId, 'outcome' => $error ?? 'ok', 'tokens' => $this->spent];

        match ($error) {
            null => Log::info('channel-ai', $context),
            'question', 'busy', 'none', 'quota', 'down' => Log::debug('channel-ai', $context),
            default => Log::warning('channel-ai', $context),
        };

        return [
            'answer' => $answer,
            'error' => $error,
            'message' => $error ? config("ai.channel.errors.{$error}") : null,
            'soft' => in_array($error, (array) config('ai.channel.soft'), true),
            'note' => trim(implode(' ', array_filter([$truncated ? config('ai.channel.truncated_note') : null, $this->cut ? config('ai.channel.cut_note') : null]))) ?: null,
            'meter' => $this->meter($userId),
        ];
    }

    public function meter(int $userId): string
    {
        $meter = Channel::quotaMeter($userId);
        $warn = $meter['pct'] >= 80 && $meter['used'] < $meter['cap']
            ? 'فقط ' . convertToPersian($meter['cap'] - $meter['used']) . ' بار دیگر امروز می‌توانید از منشی بپرسید.'
            : null;

        return Blade::render('<x-ui.quota-meter label="سهمیهٔ امروز" aria="سهمیهٔ امروز" :used="$used" :cap="$cap" :pct="$pct" :tone="$tone" :warn="$warn"/>', $meter + ['warn' => $warn]);
    }
}
