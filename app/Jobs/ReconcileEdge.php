<?php

namespace App\Jobs;

use App\Services\Menu\EdgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileEdge implements ShouldQueue
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
        EdgeService::reconcile($this->ruleKey, $this->subjectClass, $this->subjectId);
    }
}