<?php

namespace App\Livewire\Dashboard\AiReport\Actions;

use App\Models\AiReport;
use App\Models\User;
use App\Support\AiReportAccessPolicy;
use Illuminate\Support\Facades\DB;

class DeleteReportAction
{
    public function execute(AiReport $report, User $user): void
    {
        DB::transaction(function () use ($report, $user) {
            $fresh = AiReport::query()->whereKey($report->id)->lockForUpdate()->first();
            if (!$fresh || !AiReportAccessPolicy::canDelete($fresh, $user)) {
                throw new \RuntimeException('این عملیات مجاز نیست.');
            }
            $fresh->delete();
        });
    }
}
