<?php

namespace App\Services\Menu;

use App\Jobs\ReconcileEdge;
use App\Models\Edge;
use App\Services\Menu\Contracts\MenuEdge;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EdgeService
{
    private static array $rules = [];

    private static array $registered = [];

    private static array $wired = [];

    public static function register(MenuEdge $edge): void
    {
        $key = $edge->getKey();

        if (isset(self::$registered[$key])) return;

        self::$registered[$key] = true;
        self::$rules[$key] = [
            'edge' => $edge,
            'refresh' => method_exists($edge, 'refresh') ? $edge->refresh() : true,
            'hasIcon' => method_exists($edge, 'icon'),
            'hasUrl' => method_exists($edge, 'url'),
            'hasShow' => method_exists($edge, 'show'),
            'hasDismissRule' => method_exists($edge, 'dismissRule'),
            'localRoute' => method_exists($edge, 'localRoute') ? $edge->localRoute() : null,
        ];

        foreach ($edge->triggers() as $trigger) {
            $triggerClass = $trigger['class'];
            $subjectResolver = $trigger['subject'] ?? fn(Model $m) => $m;

            foreach ($trigger['on'] as $event) {
                $wireKey = "{$triggerClass}@{$event}:{$key}";

                if (isset(self::$wired[$wireKey])) continue;

                self::$wired[$wireKey] = true;

                $triggerClass::{$event}(function (Model $model) use ($key, $subjectResolver) {
                    $subject = $subjectResolver($model);

                    if ($subject === null) return;

                    dispatch(new ReconcileEdge($key, get_class($subject), $subject->getKey()))
                        ->afterCommit();
                });
            }
        }
    }

    public static function reconcile(string $ruleKey, string $subjectClass, int|string $subjectId): void
    {
        $rule = self::$rules[$ruleKey] ?? null;

        if ($rule === null) return;

        $itemId = (string) $subjectId;

        try {
            Cache::lock("edge:k{$ruleKey}:i{$itemId}", 10)->block(3, function () use ($rule, $ruleKey, $itemId, $subjectClass, $subjectId): void {
                $baseQuery = Edge::query()->matchingSubject($ruleKey, $subjectClass, $itemId);

                $subject = $subjectClass::find($subjectId);

                if ($subject === null) {
                    $baseQuery->delete();
                    return;
                }

                $edge = $rule['edge'];
                $recipients = collect($edge->for($subject));
                $ids = $recipients->pluck('id');

                if ($ids->isEmpty()) {
                    $baseQuery->delete();
                    return;
                }

                $existing = (clone $baseQuery)->whereIn('user_id', $ids)->get()->keyBy('user_id');

                (clone $baseQuery)->whereNotIn('user_id', $ids)->delete();

                foreach ($recipients as $user) {
                    if ($rule['hasShow'] && !$edge->show($subject, $user)) {
                        $existing->get($user->id)?->delete();
                        continue;
                    }

                    $payload = [
                        'icon' => $rule['hasIcon'] ? $edge->icon($subject, $user) : 'info',
                        'title' => $edge->title($subject, $user),
                        'body' => $edge->body($subject, $user),
                        'url' => $rule['hasUrl'] ? ((string) ($edge->url($subject) ?? '')) : '',
                    ];

                    $row = $existing->get($user->id);

                    if ($row !== null) {
                        if ($rule['refresh']) {
                            $row->fill($payload)->save();
                        }
                        continue;
                    }

                    Edge::create([
                            'user_id' => $user->id,
                            'edge_key' => $ruleKey,
                            'subject_type' => $subjectClass,
                            'subject_id' => $itemId,
                        ] + $payload);
                }
            });
        } catch (LockTimeoutException) {
        }
    }

    public static function forUser(int $userId): array
    {
        return Edge::query()
            ->where('user_id', $userId)
            ->visible()
            ->orderBy('id')
            ->get()
            ->map(fn(Edge $e) => [
                'key' => $e->edge_key,
                'subject_id' => $e->subject_id,
                'icon' => $e->icon,
                'title' => $e->title,
                'body' => $e->body,
                'url' => $e->url,
                'localRoute' => isset(self::$rules[$e->edge_key]['localRoute'])
                    ? self::$rules[$e->edge_key]['localRoute']
                    : null,
            ])
            ->all();
    }

    public static function dismiss(int $userId, string $edgeKey, int|string $subjectId): void
    {
        $edge = Edge::query()->for($userId, $edgeKey, $subjectId)->first();

        if ($edge === null) return;

        $rule = self::$rules[$edgeKey] ?? null;

        $optionKey = ($rule !== null && $rule['hasDismissRule']) ? $rule['edge']->dismissRule() : 'forever';

        $edge->snooze($optionKey);
    }

    public static function reset(): void
    {
        self::$rules = [];
        self::$registered = [];
    }
}