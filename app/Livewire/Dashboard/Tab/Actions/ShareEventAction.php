<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Jobs\ReconcileNudge;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\User;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

class ShareEventAction
{
    public function execute(int $eventId, int $sharerId, array $recipientIds): array
    {
        $event = Event::query()
            ->where('user_id', $sharerId)
            ->find($eventId);

        if (!$event) {
            throw new \InvalidArgumentException('رویداد یافت نشد یا متعلق به شما نیست.');
        }

        $recipientIds = $this->normalizeRecipients($recipientIds, $sharerId);

        $toAdd = [];
        $toRemove = [];
        $inserted = null;

        DB::transaction(function () use ($event, $sharerId, $recipientIds, &$toAdd, &$toRemove, &$inserted): void {
            $current = $event->shares()->pluck('user_id')->all();

            $toAdd = array_values(array_diff($recipientIds, $current));
            $toRemove = array_values(array_diff($current, $recipientIds));

            if (!empty($toAdd)) {
                $now = now();
                $rows = array_map(fn(int $userId) => [
                    'event_id' => $event->id,
                    'user_id' => $userId,
                    'shared_by' => $sharerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $toAdd);

                $inserted = EventShare::insertOrIgnore($rows);
            }

            if (!empty($toRemove)) {
                $event->shares()->whereIn('user_id', $toRemove)->delete();
            }
        });

        if (empty($toAdd) && empty($toRemove)) {
            return ['added' => 0, 'removed' => 0, 'event_title' => $event->title];
        }

        StateService::flush();
        dispatch(new ReconcileNudge('shared-events:nudge', Event::class, $event->id))->afterCommit();

        $added = is_numeric($inserted) ? (int) $inserted : count($toAdd);
        $removed = count($toRemove);

        return [
            'added' => $added,
            'removed' => $removed,
            'event_title' => $event->title,
        ];
    }

    private function normalizeRecipients(array $recipientIds, int $sharerId): array
    {
        $ids = collect($recipientIds)
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0 && $id !== $sharerId)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) return [];

        return User::query()
            ->visibleOnBoard()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
    }
}
