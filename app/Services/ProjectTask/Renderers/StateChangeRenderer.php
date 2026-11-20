<?php

namespace App\Services\ProjectTask\Renderers;

use App\Enums\TaskState;
use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class StateChangeRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return match ($this->state($reply->payload['to'] ?? null)) {
            TaskState::Extension => 'calendar_month',
            TaskState::Suspension => 'pause_circle',
            TaskState::Completion => 'check_circle',
            default => 'rule',
        };
    }

    public function getLabel(): string
    {
        return 'نتیجهٔ کار';
    }

    public function getBody(Reply $reply): string
    {
        $to = $this->state($reply->payload['to'] ?? null);

        return $to !== null
            ? "نتیجهٔ کار به «{$to->getLabel()}» تغییر کرد."
            : 'نتیجهٔ کار برداشته شد.';
    }

    private function state(?string $value): ?TaskState
    {
        return TaskState::tryFrom((string) $value);
    }
}
