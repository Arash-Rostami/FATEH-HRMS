<?php

namespace App\Services\Menu;

use App\Models\Edge;
use App\Models\Event;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PulseService
{
    public const EVENTS = [
        'notifications' => 'databaseNotificationsSent',
        'edges' => 'edges-changed',
        'reminders' => 'reminder-changed',
        'eventReminder' => 'event-reminder-changed',
    ];

    private const STAMP = "CONCAT_WS('|', COUNT(*), MAX(created_at), MAX(updated_at))";

    private const EDGE_STAMP = "CONCAT_WS('|', COUNT(*), MAX(id), MAX(updated_at))";

    public static function fingerprint(User $user): array
    {
        return (array) DB::query()
            ->selectSub($user->notifications()->reorder()->where('data->format', 'filament')->unread()->selectRaw(self::STAMP), 'notifications')
            ->selectSub(Edge::where('user_id', $user->id)->visible()->selectRaw(self::EDGE_STAMP), 'edges')
            ->selectSub(Reminder::forUser($user->id)->active()->where('due_at', '<=', now())->selectRaw(self::STAMP), 'reminders')
            ->selectSub(Event::reminderDueFor($user)->orderBy('date')->limit(1)->selectRaw("CONCAT_WS('@', id, date)"), 'eventReminder')
            ->first();
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
