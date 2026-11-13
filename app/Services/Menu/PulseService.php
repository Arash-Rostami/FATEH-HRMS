<?php

namespace App\Services\Menu;

use App\Livewire\Dashboard\Tab\Presentation\TabPresenter;
use App\Enums\PresenceStatus;
use App\Models\Edge;
use App\Models\Event;
use App\Models\Reminder;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PulseService
{
    public const EVENTS = [
        'notifications' => 'databaseNotificationsSent',
        'edges' => 'edges-changed',
        'reminders' => 'reminder-changed',
        'eventReminder' => 'event-reminder-changed',
        'homeStats' => 'home-stats-changed',
        'presence' => 'presence-changed',
    ];

    private const ONLINE_STAMP = "CONCAT_WS('|', COUNT(*), COALESCE(SUM(id), 0), COALESCE(BIT_XOR(id), 0))";

    private const STAMP = "CONCAT_WS('|', COUNT(*), MAX(created_at), MAX(updated_at))";

    private const NOTIFICATION_STAMP = "CONCAT_WS('|', COALESCE(SUM(JSON_UNQUOTE(JSON_EXTRACT(data, '\$.menu_key')) LIKE '%nudge'), 0), COUNT(*), MAX(created_at), MAX(updated_at))";

    private const EDGE_STAMP = "CONCAT_WS('|', COUNT(*), MAX(id), MAX(updated_at))";

    public static function fingerprint(User $user): array
    {
        $fingerprint = (array) DB::query()
            ->selectSub($user->notifications()->reorder()->where('data->format', 'filament')->unread()->selectRaw(self::NOTIFICATION_STAMP), 'notifications')
            ->selectSub(Edge::where('user_id', $user->id)->visible()->selectRaw(self::EDGE_STAMP), 'edges')
            ->selectSub(Reminder::forUser($user->id)->active()->where('due_at', '<=', now())->selectRaw(self::STAMP), 'reminders')
            ->selectSub(Event::reminderDueFor($user)->orderBy('date')->limit(1)->selectRaw("CONCAT_WS('@', id, date)"), 'eventReminder')
            ->selectSub(User::visibleOnBoard()->online()->selectRaw(self::ONLINE_STAMP), 'presence')
            ->first();

        $fingerprint['homeStats'] = TabPresenter::statStamp($user->id);
        $fingerprint['presence'] .= '|' . ModelCacheVersion::version(User::PRESENCE_VERSION_KEY);

        return $fingerprint;
    }

    public static function presenceMap(string $stamp): array
    {
        return Cache::remember('presence-map:' . md5($stamp), 60, function (): array {
            $map = ['on' => [], 'p' => [], 'f' => []];

            User::query()
                ->visibleOnBoard()
                ->where(fn ($q) => $q->where('last_seen', '>=', User::onlineSince())->orWhere('presence', '!=', PresenceStatus::default()->value))
                ->get(['id', 'presence', 'focus_until', 'last_seen'])
                ->each(function (User $user) use (&$map): void {
                    $user->isOnline() && $map['on'][] = $user->id;
                    $user->presence !== PresenceStatus::default() && $map['p'][$user->id] = $user->presence->value;
                    ($label = $user->focusLabel()) && $map['f'][$user->id] = $label;
                });

            return $map;
        });
    }

    public static function presenceMeta(): array
    {
        return collect(PresenceStatus::cases())
            ->mapWithKeys(fn (PresenceStatus $s) => [$s->value => ['i' => $s->icon(), 't' => $s->tip()]])
            ->all();
    }

    public static function changedEvents(array $before, array $after): array
    {
        return array_values(array_filter(
            self::EVENTS,
            static fn (string $event, string $part) => ($before[$part] ?? null) !== ($after[$part] ?? null),
            ARRAY_FILTER_USE_BOTH,
        ));
    }
}
