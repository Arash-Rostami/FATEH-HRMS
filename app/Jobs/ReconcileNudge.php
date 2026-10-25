<?php

namespace App\Jobs;

use App\Services\Menu\NudgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ReconcileNudge implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 2;

    public array $backoff = [10, 30];

    public function __construct(
        public string $ruleKey,
        public string $subjectClass,
        public int|string $subjectId,
    ) {}

    public function uniqueId(): string
    {
        return "{$this->ruleKey}|{$this->subjectClass}|{$this->subjectId}";
    }

    public function handle(): void
    {
        DB::transaction(
            fn () => NudgeService::reconcile($this->ruleKey, $this->subjectClass, $this->subjectId),
            3,
        );
    }
}
