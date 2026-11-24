<?php

namespace App\Models;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint_hash',
        'endpoint',
        'p256dh',
        'auth',
        'contact',
        'channel',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function sync(User $user, array $data): ?self
    {
        return DB::transaction(function () use ($user, $data) {
            DB::table('users')->where('id', $user->id)->lockForUpdate()->value('id');

            $hash = hash('sha256', $data['endpoint']);
            $contact = (bool) $data['scopes']['contact'];
            $channel = (bool) $data['scopes']['channel'];
            $existing = static::where('endpoint_hash', $hash)->first();

            if ($existing && $existing->user_id !== $user->id && !$existing->keysMatch($data['keys'])) {
                return null;
            }

            if (!$contact && !$channel) {
                $existing?->delete();

                return null;
            }

            $row = static::updateOrCreate(['endpoint_hash' => $hash], [
                'user_id' => $user->id,
                'endpoint' => $data['endpoint'],
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'contact' => $contact,
                'channel' => $channel,
            ]);

            $keep = static::where('user_id', $user->id)
                ->whereKeyNot($row->id)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->limit(4)
                ->pluck('id')
                ->push($row->id);

            static::where('user_id', $user->id)->whereNotIn('id', $keep)->delete();

            return $row;
        }, 3);
    }

    public function keysMatch(array $keys): bool
    {
        return hash_equals($this->p256dh, $keys['p256dh']) && hash_equals($this->auth, $keys['auth']);
    }

    public static function send(iterable $subscriptions, callable $payload, ?string $topic = null): array
    {
        $options = ['TTL' => 3600, 'urgency' => 'high'];

        if ($topic !== null && preg_match('/^[A-Za-z0-9_-]{1,32}$/', $topic)) {
            $options['topic'] = $topic;
        }

        $webPush = app(WebPush::class, [
            'auth' => ['VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public'),
                'privateKey' => config('services.webpush.private'),
            ]],
            'defaultOptions' => $options,
            'client' => new Client(['connect_timeout' => 2, 'timeout' => 4, 'allow_redirects' => false, 'http_errors' => false]),
            'logger' => Log::getLogger(),
        ]);

        $started = microtime(true);
        $gone = [];
        $problems = [];
        $skipped = [];

        foreach ($subscriptions as $subscription) {
            $host = (string) parse_url($subscription->endpoint, PHP_URL_HOST);

            if (microtime(true) - $started > 60 || Cache::has("push-down:{$host}")) {
                $skipped[] = $subscription->id;
                continue;
            }

            try {
                $report = $webPush->sendOneNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->p256dh,
                        'authToken' => $subscription->auth,
                        'contentEncoding' => 'aes128gcm',
                    ]),
                    json_encode($payload($subscription), JSON_UNESCAPED_UNICODE),
                );
            } catch (Throwable $e) {
                $problems[$e::class] = ($problems[$e::class] ?? 0) + 1;
                continue;
            }

            if ($report->isSuccess()) {
                continue;
            }

            $status = $report->getResponse()?->getStatusCode();

            if ($report->isSubscriptionExpired() || in_array($status, [404, 410], true)) {
                $gone[] = $subscription->id;
                continue;
            }

            $problems[$status ?? 'transport'] = ($problems[$status ?? 'transport'] ?? 0) + 1;

            if ($status === null) {
                Cache::put("push-down:{$host}", true, 60);
            }

            if ($status === null || $status === 429 || $status >= 500) {
                $skipped[] = $subscription->id;
            }
        }

        if ($gone !== []) {
            static::whereKey($gone)->delete();
        }

        if ($problems !== [] || $skipped !== []) {
            Log::warning('web push delivery problems', ['problems' => $problems, 'skipped' => count($skipped), 'gone' => count($gone)]);
        }

        return $skipped;
    }

    protected function casts(): array
    {
        return [
            'contact' => 'boolean',
            'channel' => 'boolean',
        ];
    }
}
