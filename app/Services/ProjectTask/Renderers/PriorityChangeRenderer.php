<?php

namespace App\Services\ProjectTask\Renderers;

use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class PriorityChangeRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'flag';
    }

    public function getLabel(): string
    {
        return 'تغییر اولویت';
    }

    public function getBody(Reply $reply): string
    {
        $payload = $reply->payload ?? [];
        $to = $this->label($payload['to'] ?? null);

        return $to !== null
            ? "اولویت وظیفه به «{$to}» تغییر کرد."
            : 'اولویت وظیفه حذف شد.';
    }

    private function label(?string $value): ?string
    {
        return $value ? TaskPriority::tryFrom($value)?->getLabel() : null;
    }
}
