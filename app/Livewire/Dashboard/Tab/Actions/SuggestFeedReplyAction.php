<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Comment;
use App\Models\Feed;
use App\Services\Ai\AiClient;
use DomainException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class SuggestFeedReplyAction
{
    private const SCHEMA = [
        'type' => 'object',
        'properties' => ['reply' => ['type' => 'string']],
        'required' => ['reply'],
        'additionalProperties' => false,
    ];

    public function __construct(private AiClient $client)
    {
    }

    public function execute(int $feedId, ?int $parentId = null): array
    {
        $userId = (int) auth()->id();

        if (!$userId || !feedAiOn() || Cache::has('feed:ai:down')) {
            return $this->result($userId, '', 'unreachable');
        }

        $feed = Feed::forViewer()->whereKey($feedId)->first(['id', 'content']);
        $caption = $this->plain($feed?->content);
        $parent = $parentId ? $this->plain(Comment::where('feed_id', $feedId)->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $userId))->whereKey($parentId)->value('content')) : '';

        if (!$feed || ($parentId && $parent === '') || ($caption === '' && $parent === '')) {
            return $this->result($userId, '', 'none');
        }

        if (AiClient::cooldownActive('feed', $userId)) {
            return $this->result($userId, '', 'busy');
        }

        $day = now()->format('Y-m-d');

        if (Feed::consumeQuota($userId, $day) > Feed::quotaCap()) {
            Feed::refundQuota($userId, $day);

            return $this->result($userId, '', 'quota');
        }

        try {
            $reply = $this->ask($caption, $parent);
        } catch (Throwable $e) {
            Feed::refundQuota($userId, $day);

            $code = $e instanceof DomainException ? $e->getMessage() : 'unreachable';

            if (!$e instanceof DomainException && !$e instanceof RequestException) {
                report($e);
            }

            if ($e instanceof RequestException ? ($e->response->status() >= 500 || $e->response->status() === 429) : $code === 'unreachable') {
                Cache::put('feed:ai:down', 1, (int) AiClient::setting('feed', 'down_for'));
            }

            return $this->result($userId, '', in_array($code, ['blocked', 'too_large', 'empty'], true) ? 'empty' : 'unreachable');
        } finally {
            AiClient::startCooldown('feed', $userId);
        }

        return $this->result($userId, $reply, null);
    }

    private function plain(mixed $text): string
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text)))), 0, (int) config('ai.feed.max_chars'));
    }

    private function ask(string $caption, string $parent): string
    {
        $cut = fn (string $text): string => preg_replace('/[<>]{3,}/u', '', $text);
        $fence = Str::random(12);
        $parentLine = $parent !== '' ? "COMMENT TO REPLY TO: {$cut($parent)}\n" : '';

        $messages = [
            ['role' => 'system', 'content' => (string) config('ai.feed.rules')],
            ['role' => 'user', 'content' => "FENCE: {$fence}\nTASK: Draft one short Persian comment-reply to the company feed post below.\n<<<POST:{$fence}>>>\n{$cut($caption)}\n{$parentLine}<<<END:{$fence}>>>"],
        ];

        $content = $this->client->json($messages, self::SCHEMA, 'feed')['content'];

        if ($content === null) {
            throw new DomainException('empty');
        }

        try {
            $reply = AiClient::parseJson($content)['reply'] ?? '';
        } catch (JsonException) {
            $reply = '';
        }

        return AiClient::cleanText(is_string($reply) ? $reply : '', false, (int) config('ai.feed.reply_max')) ?: throw new DomainException('empty');
    }

    private function result(int $userId, string $reply, ?string $error): array
    {
        $context = ['user' => $userId, 'outcome' => $error ?? 'ok'];

        match ($error) {
            null => Log::info('feed-ai', $context),
            'none', 'quota', 'busy' =>Log::debug('feed-ai', $context),
            default => Log::warning('feed-ai', $context),
        };

        return ['reply' => $reply, 'error' => $error, 'message' => $error ? config("ai.feed.errors.{$error}") : null];
    }
}
