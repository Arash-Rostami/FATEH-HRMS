<?php

namespace App\Services\Notify;

use App\Enums\NotifyEvent;
use App\Jobs\SendNotifyRule;
use App\Models\NotifyRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

final class NotifyService
{
    public const CACHE_KEY = 'notify:rules';

    private const BOOKKEEPING = ['updated_at', 'notified_at', 'deleted_at'];

    public static function handle(Model $model, NotifyEvent $event): void
    {
        if (tenantOff('notify')) {
            return;
        }

        try {
            $updated = $event === NotifyEvent::Updated;
            $changed = $updated ? array_keys($model->getChanges()) : null;

            if ($updated && array_diff($changed, self::BOOKKEEPING) === []) {
                return;
            }

            $rules = self::rulesFor($model::class)->filter(fn (NotifyRule $r) => $r->fires($event) && $r->isLive());

            if ($rules->isEmpty()) {
                return;
            }

            $changed ??= array_keys($model->getChanges());

            if ($updated) {
                $rules = $rules->filter(fn (NotifyRule $r) => $r->watches($changed));

                if ($rules->isEmpty()) {
                    return;
                }
            }

            $rules = $rules->filter(fn (NotifyRule $r) => $r->matches($model));

            if ($rules->isEmpty()) {
                return;
            }

            $payload = self::buildPayload($model, $event, $changed, $event === NotifyEvent::Deleted);

            foreach ($rules as $rule) {
                dispatch(new SendNotifyRule($rule->id, $payload))->afterCommit();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function rulesFor(string $modelClass): Collection
    {
        $all = Cache::remember(self::CACHE_KEY, now()->addDay(), fn () => NotifyRule::where('is_active', true)->where('model', '!=', NotifyRule::ONE_OFF)->get()->groupBy('model'));

        return $all->get($modelClass, collect());
    }

    public static function payloadFor(Model $model, NotifyEvent $event): array
    {
        return self::buildPayload($model, $event, array_keys($model->getChanges()));
    }

    private static function buildPayload(Model $model, NotifyEvent $event, array $changed, bool $withValues = true): array
    {
        $payload = [
            'model' => $model::class,
            'id' => $model->getKey(),
            'event' => $event->value,
            'label' => $model::notifyLabel(),
            'changed' => $changed,
            'actor' => Auth::user()?->name,
            'at' => now()->toDateTimeString(),
        ];

        return $withValues ? $payload + ['values' => $model->notifyValues()] : $payload;
    }

    public static function samplePayload(NotifyRule $rule): array
    {
        $class = $rule->model;

        if (! array_key_exists($class, NotifyRegistry::permitted())) {
            throw new RuntimeException("Model {$class} no longer supports notifications.");
        }

        $event = NotifyEvent::from($rule->events[0] ?? 'updated');
        $record = $class::query()->latest((new $class)->getKeyName())->first();

        if ($record) {
            return self::payloadFor($record, $event);
        }

        return [
            'model' => $class,
            'id' => 0,
            'event' => $event->value,
            'label' => $class::notifyLabel(),
            'values' => array_fill_keys($class::notifyPlaceholders(), '…'),
            'changed' => [],
            'actor' => Auth::user()?->name,
            'at' => now()->toDateTimeString(),
        ];
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
