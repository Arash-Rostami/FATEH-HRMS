<?php

namespace App\Livewire\Dashboard;

use App\Models\DMS;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Livewire\Attributes\On;

class UnreadNotifications extends DatabaseNotifications
{
    private const DMS_KEY = 'dms-controller:nudge';

    private const AGGREGATE_THRESHOLD = 4;

    protected ?array $dmsCountsCache = null;

    public function clearNotificationsAction(): Action
    {
        return parent::clearNotificationsAction()->hidden();
    }

    public function isPaginated(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public function getNotificationsQuery(): Builder|Relation
    {
        $query = parent::getNotificationsQuery();

        if (Filament::getCurrentPanel()?->getId() !== 'admin') {
            $query->unread();
        }

        return $query;
    }

    #[On('notificationClosed')]
    public function removeNotification(string $id): void
    {
        if (str_starts_with($id, 'agg:')) {
            $payload = substr($id, 4);
            $key = preg_match('/:(?:sign|read)$/', $payload)
                ? substr($payload, 0, (int) strrpos($payload, ':'))
                : $payload;

            $this->getNotificationsQuery()
                ->where('data->menu_key', $key)
                ->update(['read_at' => now()]);

            return;
        }

        parent::removeNotification($id);
    }

    public function getNotifications(): DatabaseNotificationCollection | Paginator
    {
        $items = parent::getNotifications();

        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            return $items;
        }

        $counts = $items->countBy(fn (DatabaseNotification $n) => $n->data['menu_key'] ?? null);
        $emitted = [];
        $folded = [];

        foreach ($items as $n) {
            $key = $n->data['menu_key'] ?? null;

            if ($key === null || $counts[$key] <= self::AGGREGATE_THRESHOLD) {
                $folded[] = $n;
                continue;
            }

            if (isset($emitted[$key])) {
                continue;
            }

            if ($key === self::DMS_KEY) {
                $userId = (int) auth()->id();
                [$sign, $read] = $this->dmsCounts($userId);

                if ($sign === 0 && $read === 0) {
                    $folded[] = $n;
                    continue;
                }

                $emitted[$key] = true;

                if ($sign > 0) {
                    $folded[] = $this->dmsAggregateRow('sign', $sign);
                }

                if ($read > 0) {
                    $folded[] = $this->dmsAggregateRow('read', $read);
                }

                continue;
            }

            $emitted[$key] = true;
            $folded[] = $this->genericAggregateRow($key, $counts[$key], $n);
        }

        return new DatabaseNotificationCollection($folded);
    }

    public function getUnreadNotificationsCount(): int
    {
        $total = parent::getUnreadNotificationsCount();

        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            return $total;
        }

        $groups = $this->getNotificationsQuery()
            ->reorder()
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.menu_key')) as menu_key, COUNT(*) as cnt")
            ->groupBy('menu_key')
            ->get();

        $userId = null;

        foreach ($groups as $g) {
            $key = $g->menu_key;
            $cnt = (int) $g->cnt;

            if ($key === null || $cnt <= self::AGGREGATE_THRESHOLD) {
                continue;
            }

            if ($key === self::DMS_KEY) {
                $userId ??= (int) auth()->id();
                [$sign, $read] = $this->dmsCounts($userId);

                if ($sign === 0 && $read === 0) {
                    continue;
                }

                $aggregates = ($sign > 0 ? 1 : 0) + ($read > 0 ? 1 : 0);
                $total += $aggregates - $cnt;
            } else {
                $total += 1 - $cnt;
            }
        }

        return max(0, $total);
    }

    public function markAllNotificationsAsReadAction(): Action
    {
        return parent::markAllNotificationsAsReadAction()->label('حذف همه اعلانات');
    }

    protected function dmsCounts(int $userId): array
    {
        return $this->dmsCountsCache ??= [
            DMS::needsSignCount($userId),
            DMS::needsReadCount($userId),
        ];
    }

    protected function dmsAggregateRow(string $mode, int $count): DatabaseNotification
    {
        $title = $mode === 'sign'
            ? "{$count} سند نیازمند تأیید شماست"
            : "{$count} سند نیازمند مطالعه شماست";

        return $this->makeAggregateRow($title, 'برای مشاهده به مدیریت اسناد مراجعه کنید.', self::DMS_KEY, $mode);
    }

    protected function genericAggregateRow(string $key, int $count, DatabaseNotification $representative): DatabaseNotification
    {
        $latest = superClean($representative->data['title'] ?? $key, 100);

        return $this->makeAggregateRow("{$count} اعلان جدید", "آخرین: {$latest}", $key);
    }

    protected function makeAggregateRow(string $title, string $body, string $key, ?string $mode = null): DatabaseNotification
    {
        $id = 'agg:' . $key . ($mode !== null ? ":{$mode}" : '');

        return new DatabaseNotification([
            'id' => $id,
            'type' => FilamentDatabaseNotification::class,
            'data' => Notification::make()
                ->title($title)
                ->body($body)
                ->warning()
                ->persistent()
                ->getDatabaseMessage(),
            'read_at' => null,
            'created_at' => now(),
        ]);
    }
}