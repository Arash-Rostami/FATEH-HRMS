<?php

namespace App\Livewire\Dashboard\AiReport\Actions;

use App\Models\AiReport;
use App\Models\User;
use App\Support\AiReportAccessPolicy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RestoreReportAction
{
    public function execute(AiReport $report, User $user): void
    {
        DB::transaction(function () use ($report, $user) {
            $fresh = AiReport::query()->whereKey($report->id)->lockForUpdate()->first();

            if (!$fresh || !AiReportAccessPolicy::canRestore($fresh, $user)) {
                throw new RuntimeException('restore denied');
            }

            $versions = array_values($fresh->versions ?? []);

            while (count($versions) > 1 && !isset($versions[array_key_last($versions)]['spec'])) {
                array_pop($versions);
            }

            $fresh->versions = $versions;

            $fresh->status = AiReport::STATUS_READY;
            $fresh->fail_reason = null;
            $fresh->save();
            $report->setRawAttributes($fresh->getAttributes(), true);
        });
    }
}
