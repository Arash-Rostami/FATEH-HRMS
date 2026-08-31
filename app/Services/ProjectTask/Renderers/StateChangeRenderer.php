<?php

namespace App\Services\ProjectTask\Renderers;

use App\Filament\Resources\TaskResource\Enums\TaskState;
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
        return 'تعیین تکلیف';
    }

    public function getBody(Reply $reply): string
    {
        $to = $this->state($reply->payload['to'] ?? null);

        return $to !== null
            ? "تعیین تکلیف وظیفه به «{$to->getLabel()}» تغییر کرد."
            : 'تعیین تکلیف وظیفه حذف شد.';
    }

    private function state(?string $value): ?TaskState
    {
        return TaskState::tryFrom((string) $value);
    }
}
