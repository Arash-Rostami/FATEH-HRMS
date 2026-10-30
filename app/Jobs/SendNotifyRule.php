<?php

namespace App\Jobs;

use App\Models\Concerns\HasNotifyEvents;
use App\Models\NotifyLog;
use App\Models\NotifyRule;
use App\Services\Notify\Message;
use App\Services\Notify\MessageBuilder;
use App\Services\Notify\RecipientResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
use Throwable;

class SendNotifyRule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private const INSERT_CHUNK = 500;

    public int $tries = 1;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public string $runId;

    private ?Model $record = null;

    public function __construct(public int $ruleId, public array $payload)
    {
        $this->runId = (string) Str::ulid();
    }

    public function handle(MessageBuilder $builder, RecipientResolver $resolver): void
    {
        $rule = NotifyRule::find($this->ruleId);

        if ($rule === null || ! $rule->is_active) {
            return;
        }

        $this->runId ??= (string) Str::ulid();
        $runId = $this->runId;
        $event = $this->payload['event'] ?? null;
        $recordId = isset($this->payload['id']) ? (string) $this->payload['id'] : null;
        $row = fn (array $attributes) => array_merge([
            'notify_rule_id' => $rule->id,
            'run_id' => $runId,
            'source' => NotifyLog::SOURCE_RULE,
            'sender_id' => null,
            'count' => 1,
            'user_id' => null,
            'event' => $event,
            'record_id' => $recordId,
            'error' => null,
            'created_at' => now(),
        ], $attributes);
        $summary = fn (int $count, ?string $error) => $row([
            'count' => $count,
            'recipient' => __('resources/notify_rule/strings.batch.summary'),
            'channel' => 'all',
            'status' => NotifyLog::STATUS_SUMMARY,
            'error' => $error,
        ]);
        $written = false;

        try {
            $payload = $this->withValues();

            if ($payload === null) {
                NotifyLog::insert([$summary(0, __('resources/notify_rule/strings.batch.record_gone'))]);

                return;
            }

            if ($this->record !== null && ! $rule->matches($this->record)) {
                NotifyLog::insert([$summary(0, __('resources/notify_rule/strings.batch.conditions_changed'))]);

                return;
            }

            $message = $builder->build($rule, $payload);
            [$resolved, $skipped] = $resolver->resolveWithSkips($rule);

            if ($resolved === []) {
                NotifyLog::insert([$summary(0, __('resources/notify_rule/strings.batch.no_channel'))]);

                return;
            }

            $targeted = $this->targeted($resolved, $skipped);
            $ceiling = (int) config('notify.ceiling');

            if ($targeted > $ceiling) {
                NotifyLog::insert([$summary($targeted, __('resources/notify_rule/strings.batch.ceiling', ['count' => $ceiling]))]);

                return;
            }

            $rows = [$summary($targeted, null)];

            foreach ($skipped as $key => $people) {
                if (count($people) > (int) config('notify.detail_below')) {
                    foreach (collect($people)->countBy('reason') as $reason => $count) {
                        $rows[] = $row([
                            'count' => $count,
                            'recipient' => __('resources/notify_rule/strings.batch.skipped_many', ['count' => $count]),
                            'channel' => $key,
                            'status' => NotifyLog::STATUS_SKIPPED,
                            'error' => $reason,
                        ]);
                    }

                    continue;
                }

                foreach ($people as $person) {
                    $rows[] = $row([
                        'user_id' => $person['user_id'],
                        'recipient' => mb_substr($person['name'], 0, 191),
                        'channel' => $key,
                        'status' => NotifyLog::STATUS_SKIPPED,
                        'error' => $person['reason'],
                    ]);
                }
            }

            foreach (array_chunk($rows, self::INSERT_CHUNK) as $part) {
                NotifyLog::insert($part);
                $written = true;
            }

            $this->dispatchChunks($rule->id, $runId, $message, $resolved, $event, $recordId);
        } catch (Throwable $e) {
            report($e);

            $error = __('resources/notify_rule/strings.batch.run_failed');

            if ($written) {
                NotifyLog::where('run_id', $runId)->where('status', NotifyLog::STATUS_SUMMARY)->update(['error' => $error]);
            } else {
                NotifyLog::insert([$summary(0, $error)]);
            }
        }
    }

    public function failed(Throwable $e): void
    {
        $this->runId ??= (string) Str::ulid();

        $updated = NotifyLog::where('run_id', $this->runId)
            ->where('status', NotifyLog::STATUS_SUMMARY)
            ->update(['error' => __('resources/notify_rule/strings.batch.run_failed')]);

        if ($updated > 0) {
            return;
        }

        NotifyLog::insert([[
            'notify_rule_id' => $this->ruleId,
            'run_id' => $this->runId,
            'source' => NotifyLog::SOURCE_RULE,
            'count' => 0,
            'event' => $this->payload['event'] ?? null,
            'record_id' => isset($this->payload['id']) ? (string) $this->payload['id'] : null,
            'recipient' => __('resources/notify_rule/strings.batch.summary'),
            'channel' => 'all',
            'status' => NotifyLog::STATUS_SUMMARY,
            'error' => __('resources/notify_rule/strings.batch.run_failed'),
            'created_at' => now(),
        ]]);
    }

    private function withValues(): ?array
    {
        $class = $this->payload['model'] ?? null;
        $values = [];
        $this->record = null;

        if (! array_key_exists('values', $this->payload) && is_string($class) && class_exists($class)
            && in_array(HasNotifyEvents::class, class_uses_recursive($class), true)) {
            $query = in_array(SoftDeletes::class, class_uses_recursive($class), true)
                ? $class::withTrashed()
                : $class::query();
            $record = $query->find($this->payload['id'] ?? null);

            if ($record === null) {
                return null;
            }

            $this->record = $record;
            $values = $record->notifyValues();
        }

        return $this->payload + ['values' => $values];
    }

    private function targeted(array $resolved, array $skipped): int
    {
        $people = [];

        foreach ($resolved as $addresses) {
            foreach ($addresses as $address => $userId) {
                $people[$userId === null ? 'c:'.$address : 'u:'.$userId] = true;
            }
        }

        foreach ($skipped as $list) {
            foreach ($list as $person) {
                $people['u:'.$person['user_id']] = true;
            }
        }

        return count($people);
    }

    private function dispatchChunks(int $ruleId, string $runId, Message $message, array $resolved, ?string $event, ?string $recordId): void
    {
        $step = 0;
        $spacing = (int) config('notify.delay');

        foreach ($resolved as $key => $addresses) {
            $detailed = count($addresses) <= (int) config('notify.detail_below');

            foreach (array_chunk($addresses, max(1, (int) config("notify.chunk.{$key}", 50)), true) as $part) {
                dispatch(new SendNotifyChunk($ruleId, $runId, $key, $message, $part, $detailed, $event, $recordId))
                    ->delay(now()->addSeconds($step++ * $spacing));
            }
        }
    }
}
