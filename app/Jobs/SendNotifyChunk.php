<?php

namespace App\Jobs;

use App\Models\NotifyLog;
use App\Services\Notify\Message;
use App\Services\Notify\NotifyRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
use Throwable;

class SendNotifyChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private const ERROR_LIMIT = 500;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $ruleId,
        public string $runId,
        public string $channelKey,
        public Message $message,
        public array $addresses,
        public bool $detailed,
        public ?string $event,
        public ?string $recordId,
    ) {
        $this->timeout = (int) config('notify.timeout', 60);
    }

    public function handle(): void
    {
        try {
            $channel = NotifyRegistry::channel($this->channelKey);

            $error = match (true) {
                $channel === null => __('resources/notify_rule/strings.batch.channel_off'),
                ! $channel->isConfigured() => __('resources/notify_rule/strings.batch.not_configured', ['channel' => $channel->label()]),
                default => null,
            };

            $rows = $error === null
                ? $this->rows($channel->sendMany($this->message, array_map('strval', array_keys($this->addresses))))
                : [$this->failedChunk($error)];
        } catch (Throwable $e) {
            report($e);

            $rows = [$this->failedChunk($this->scrub($e->getMessage()))];
        }

        try {
            NotifyLog::insert($rows);
        } catch (Throwable $e) {
            report($e);
            usleep(200000);

            try {
                NotifyLog::insert($rows);
            } catch (Throwable $retry) {
                report($retry);
            }
        }
    }

    public function failed(Throwable $e): void
    {
        NotifyLog::insert([$this->failedChunk($this->scrub($e->getMessage()))]);
    }

    private function rows(array $results): array
    {
        $failures = array_filter($results, fn ($error) => $error !== null);

        if (! $this->detailed && count($this->addresses) > 1 && count($failures) === count($this->addresses) && count(array_unique($failures)) === 1) {
            return [$this->failedChunk((string) reset($failures))];
        }

        $rows = [];
        $sent = 0;

        foreach ($this->addresses as $address => $userId) {
            $error = array_key_exists((string) $address, $results) ? $results[(string) $address] : __('resources/notify_rule/strings.batch.unknown_result');

            if ($error !== null) {
                $rows[] = $this->row(['recipient' => (string) $address, 'user_id' => $userId, 'status' => NotifyLog::STATUS_FAILED, 'error' => $this->scrub((string) $error)]);
            } elseif ($this->detailed) {
                $rows[] = $this->row(['recipient' => (string) $address, 'user_id' => $userId, 'status' => NotifyLog::STATUS_SENT]);
            } else {
                $sent++;
            }
        }

        if ($sent > 0) {
            $rows[] = $this->row(['count' => $sent, 'recipient' => __('resources/notify_rule/strings.batch.recipients', ['count' => $sent]), 'status' => NotifyLog::STATUS_SENT]);
        }

        return $rows;
    }

    private function failedChunk(string $error): array
    {
        $count = count($this->addresses);

        return $this->row([
            'count' => $count,
            'recipient' => __('resources/notify_rule/strings.batch.recipients', ['count' => $count]),
            'status' => NotifyLog::STATUS_FAILED,
            'error' => $this->scrub($error),
        ]);
    }

    private function scrub(string $error): string
    {
        $key = (string) config('services.kavenegar.key');

        return mb_substr($key === '' ? $error : str_replace($key, '***', $error), 0, self::ERROR_LIMIT);
    }

    private function row(array $attributes): array
    {
        $row = array_merge([
            'notify_rule_id' => $this->ruleId,
            'run_id' => $this->runId,
            'source' => NotifyLog::SOURCE_RULE,
            'sender_id' => null,
            'count' => 1,
            'user_id' => null,
            'event' => $this->event,
            'record_id' => $this->recordId,
            'channel' => $this->channelKey,
            'error' => null,
            'created_at' => now(),
        ], $attributes);

        $row['recipient'] = $row['channel'] === 'sms' && $row['user_id'] !== null
            ? Str::mask($row['recipient'], '*', 0, -4)
            : mb_substr($row['recipient'], 0, 191);

        return $row;
    }
}
