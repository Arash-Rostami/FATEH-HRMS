<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Jobs\ReconcileNudgeJob;
use App\Jobs\SendCorrespondenceJob;
use App\Models\Correspondence;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

class MarkOpenedAction
{
    private const STATUSES = [Correspondence::STATUS_SHARED, Correspondence::STATUS_RECEIVED, Correspondence::STATUS_ROUTED];

    public function execute(Correspondence $letter, int $userId): void
    {
        if (!in_array($letter->status, self::STATUSES, true) ||!is_array($letter->parties) || $this->pendingIndex($letter->parties, $userId) === null) {
            return;
        }

        DB::transaction(function () use ($letter, $userId): void {
            $locked = Correspondence::query()->whereKey($letter->getKey())->lockForUpdate()->first(['id', 'status', 'parties', 'events']);

            if (!$locked || !in_array($locked->status, self::STATUSES, true) || !is_array($locked->parties)) {
                return;
            }

            $parties = $locked->parties;
            $index = $this->pendingIndex($parties, $userId);

            if ($index === null) {
                return;
            }

            $now = now()->toIso8601String();
            $parties[$index]['opened_at'] = $now;
            $columns = ['parties' => $parties];
            $events = is_array($locked->events) ? $locked->events : [];
            if (count($events) < LetterFlow::SOFT_EVENTS) {
                $events[] = ['at' => $now, 'user_id' => $userId, 'action' => 'opened', 'target_id' => $userId];
                $columns['events'] = $events;
            }
            SendCorrespondenceJob::writeQuiet($locked, $columns);
            if ($locked->status !== Correspondence::STATUS_SHARED) {
                dispatch(new ReconcileNudgeJob('correspondence-controller:nudge', Correspondence::class, $locked->id))->afterCommit();
            }
            DB::afterCommit(fn () => StateService::flush());
        });
    }

    private function pendingIndex(array $parties, int $userId): ?int
    {
        foreach ($parties as $index => $party) {
            if (is_array($party) && (int) ($party['user_id'] ?? 0) === $userId && blank($party['opened_at'] ?? null)) {
                return $index;
            }
        }

        return null;
    }
}
