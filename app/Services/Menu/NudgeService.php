<?php

namespace App\Services\Menu;

use App\Jobs\ReconcileNudge;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Filament\Actions\Action;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NudgeService
{
    private static array $rules = [];

    private static array $registered = [];

    private static array $wired = [];

    public static function reconcile(string $ruleKey, string $subjectClass, int|string $subjectId): void
    {
        $rule = self::$rules[$ruleKey] ?? null;

        if ($rule === null) {
            return;
        }

        $itemId = (string)$subjectId;

        try {
            Cache::lock("nudge:k{$ruleKey}:i{$itemId}", 10)->block(3, function () use ($rule, $ruleKey, $itemId, $subjectClass, $subjectId): void {
                $query = DatabaseNotification::query()
                    ->where('type', FilamentDatabaseNotification::class)
                    ->where('notifiable_type', User::class)
                    ->where('data->menu_key', $ruleKey)
                    ->where('data->item_id', $itemId);

                $subject = $subjectClass::find($subjectId);

                if ($subject === null) {
                    $query->delete();
                    return;
                }

                $recipients = ($rule['for'])($subject);
                $ids = [];

                foreach ($recipients as $user) {
                    $ids[] = $user->id;
                }

                if (empty($ids)) {
                    $query->delete();
                    return;
                }

                $existingNotifications = (clone $query)->whereIn('notifiable_id', $ids)->get()->keyBy('notifiable_id');

                $query->whereNotIn('notifiable_id', $ids)->delete();

                $suppressedIds = [];
                if ($rule['badge_suppress']) {
                    $suppressedIds = DatabaseNotification::query()
                        ->where('type', FilamentDatabaseNotification::class)
                        ->where('notifiable_type', User::class)
                        ->whereIn('notifiable_id', $ids)
                        ->where('data->menu_key', Str::beforeLast($ruleKey, ':nudge'))
                        ->whereNull('read_at')
                        ->pluck('notifiable_id')
                        ->flip()
                        ->toArray();
                }

                $show = $rule['show'];
                $title = $rule['title'];
                $body = $rule['body'];

                foreach ($recipients as $user) {
                    if (!$show($subject, $user)) {
                        if ($existingNotifications->has($user->id)) {
                            $existingNotifications->get($user->id)->delete();
                        }
                        continue;
                    }

                    $existing = $existingNotifications->get($user->id);

                    if ($existing !== null) {
                        if (($rule['refresh'] ?? false) && $existing->read_at === null) {
                            $data = self::buildData($subject, $user, $title, $body, $ruleKey, $itemId);
                            if ($existing->data != $data) {
                                $existing->update(['data' => $data]);
                            }
                        }
                        continue;
                    }

                    if ($rule['badge_suppress'] && isset($suppressedIds[$user->id])) {
                        continue;
                    }

                    $user->notifications()->create([
                        'id' => (string)Str::uuid(),
                        'type' => FilamentDatabaseNotification::class,
                        'data' => self::buildData($subject, $user, $title, $body, $ruleKey, $itemId),
                    ]);
                }
            });
        } catch (LockTimeoutException $e) {
            \Illuminate\Support\Facades\Log::warning('NudgeService reconcile lock timeout', [
                'ruleKey' => $ruleKey,
                'subjectClass' => $subjectClass,
                'subjectId' => $subjectId,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public static function register(MenuNudge $nudge): void
    {
        $key = $nudge->getKey();

        if (isset(self::$registered[$key])) {
            return;
        }

        self::$registered[$key] = true;
        self::$rules[$key] = [
            'key' => $key,
            'refresh' => $nudge->refresh(),
            'show' => fn($subject, $user) => $nudge->show($subject, $user),
            'for' => fn($subject) => $nudge->for($subject),
            'title' => fn($subject, $user) => $nudge->title($subject, $user),
            'body' => fn($subject, $user) => $nudge->body($subject, $user),
            'badge_suppress' => method_exists($nudge, 'badgeSuppressesCreate') ? $nudge->badgeSuppressesCreate() : true,
        ];

        foreach ($nudge->triggers() as $trigger) {
            $triggerClass = $trigger['class'];
            $subjectResolver = $trigger['subject'] ?? fn(Model $m) => $m;

            foreach ($trigger['on'] as $event) {
                $wireKey = "{$triggerClass}@{$event}:{$key}";

                if (isset(self::$wired[$wireKey])) {
                    continue;
                }

                self::$wired[$wireKey] = true;

                $triggerClass::{$event}(function (Model $model) use ($key, $subjectResolver) {
                    $subject = $subjectResolver($model);

                    if ($subject === null) {
                        return;
                    }

                    dispatch(new ReconcileNudge($key, get_class($subject), $subject->getKey()))
                        ->afterCommit();
                });
            }
        }
    }

    public static function reset(): void
    {
        self::$rules = [];
        self::$registered = [];
    }

    private static function buildData(
        Model           $subject,
        User            $user,
        callable        $title,
        callable|string $body,
        string          $ruleKey,
        string          $itemId,
    ): array
    {
        return [
            ...Notification::make()
                ->title($title($subject, $user))
                ->body(is_string($body) ? $body : $body($subject, $user))
                ->warning()
                ->persistent()
                ->actions([
                    Action::make('read')
                        ->label('حذف اعلان')
                        ->color('primary')
                        ->markAsRead()
                        ->button(),
                ])
                ->getDatabaseMessage(),
            'menu_key' => $ruleKey,
            'item_id' => $itemId,
        ];
    }
}
