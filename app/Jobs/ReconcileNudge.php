<?php

namespace App\Jobs;

use App\Services\Menu\NudgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileNudge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(
        public string $ruleKey,
        public string $subjectClass,
        public int|string $subjectId,
    ) {}

    public function handle(): void
    {
        NudgeService::reconcile($this->ruleKey, $this->subjectClass, $this->subjectId);
    }
}