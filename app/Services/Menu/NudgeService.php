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
        ];

        foreach ($nudge->triggers() as $trigger) {
            $triggerClass = $trigger['class'];
            $subjectResolver = $trigger['subject'] ?? fn(Model $m) => $m;

            foreach ($trigger['on'] as $event) {
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
        Model $subject,
        User $user,
        callable $title,
        callable|string $body,
        string $ruleKey,
        string $itemId,
    ): array {
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

    public static function reconcile(string $ruleKey, string $subjectClass, int|string $subjectId): void
    {
        $rule = self::$rules[$ruleKey] ?? null;

        if ($rule === null) {
            return;
        }

        $itemId = (string)$subjectId;

        try {
            Cache::lock("nudge:k{$ruleKey}:i{$itemId}", 10)->block(3, function () use ($rule, $ruleKey, $itemId, $subjectClass, $subjectId) {
                $fresh = fn() => DatabaseNotification::query()
                    ->where('type', FilamentDatabaseNotification::class)
                    ->where('notifiable_type', User::class)
                    ->where('data->menu_key', $ruleKey)
                    ->where('data->item_id', $itemId);

                $subject = $subjectClass::find($subjectId);

                if ($subject === null) {
                    $fresh()->delete();
                    return;
                }

                $recipients = ($rule['for'])($subject);
                $ids = [];

                foreach ($recipients as $user) {
                    $ids[] = $user->id;
                }

                if (empty($ids)) {
                    $fresh()->delete();
                    return;
                }

                $fresh()->whereNotIn('notifiable_id', $ids)->delete();

                $show = $rule['show'];
                $title = $rule['title'];
                $body = $rule['body'];

                foreach ($recipients as $user) {
                    if (!$show($subject, $user)) {
                        $fresh()->where('notifiable_id', $user->id)->delete();
                        continue;
                    }

                    $existing = $fresh()->where('notifiable_id', $user->id)->first();

                    if ($existing !== null) {
                        if (($rule['refresh'] ?? false) && $existing->read_at === null) {
                            $existing->update(['data' => self::buildData($subject, $user, $title, $body, $ruleKey, $itemId)]);
                        }
                        continue;
                    }

                    $user->notifications()->create([
                        'id' => (string) Str::uuid(),
                        'type' => FilamentDatabaseNotification::class,
                        'data' => self::buildData($subject, $user, $title, $body, $ruleKey, $itemId),
                    ]);
                }
            });
        } catch (LockTimeoutException) {
        }
    }
}
