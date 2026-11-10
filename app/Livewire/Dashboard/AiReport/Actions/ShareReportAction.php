<?php

namespace App\Livewire\Dashboard\AiReport\Actions;

use App\Jobs\ReconcileNudgeJob;
use App\Models\AiReport;
use App\Models\User;
use App\Services\Menu\StateService;
use App\Support\AiReportAccessPolicy;
use Illuminate\Support\Facades\DB;

class ShareReportAction
{
    public function execute(AiReport $report, User $user, array $recipientIds): array
    {
        if (!AiReportAccessPolicy::canShare($report, $user)) {
            throw new \RuntimeException('این عملیات مجاز نیست.');
        }

        $wanted = array_slice(array_values(array_unique(array_filter(array_map('intval', $recipientIds)))), 0, AiReport::maxRecipients() * 2);
        $board = $wanted === []
            ? collect()
            : User::query()->visibleOnBoard()->whereIn('id', $wanted)->get(['id', 'name'])->keyBy(fn (User $row) => (int) $row->id);
        [$merged, $added, $removed, $stamp, $truncated] = DB::transaction(function () use ($report, $user, $wanted, $board) {
            $locked = AiReport::query()->whereKey($report->id)->lockForUpdate()->first();
            if (!$locked) {
                throw new \RuntimeException('این عملیات مجاز نیست.');
            }
            $existing = array_values(array_unique(array_map('intval', $locked->recipients ?? [])));
            $cap = AiReport::maxRecipients();
            $eligible = [];
            foreach ($wanted as $id) {
                if ($id !== (int) $locked->user_id && isset($board[$id])) {
                    $eligible[] = $id;
                }
            }
            $truncated = count($eligible) > $cap;
            $merged = array_values(array_slice($eligible, 0, $cap));
            $stamp = $locked->saved_at ?? now();
            $scope = fn () => AiReport::query()
                ->whereKey($report->id)
                ->where('user_id', $user->id)
                ->where('active', true)
                ->where('status', AiReport::STATUS_READY);
            $written = $scope()->update([
                'saved_at' => $stamp,
                'recipients' => json_encode($merged, JSON_THROW_ON_ERROR),
                'seen_by' => json_encode(array_values(array_intersect(array_map('intval', $locked->seen_by ?? []), $merged)), JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
            if ($written !== 1 && !$scope()->exists()) {
                throw new \RuntimeException('این عملیات مجاز نیست.');
            }

            return [
                $merged,
                array_values(array_diff($merged, $existing)),
                array_values(array_diff($existing, $merged)),
                $stamp,
                $truncated,
            ];
        });
        $report->saved_at = $stamp;
        $report->recipients = $merged;

        if ($added !== [] || $removed !== []) {
            StateService::flush();
            dispatch(new ReconcileNudgeJob('ai-report-controller:nudge', AiReport::class, $report->id));
        }

        return [
            'added' => count($added),
            'removed' => count($removed),
            'total' => count($merged),
            'truncated' => $truncated,
        ];
    }
}
