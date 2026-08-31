<?php

namespace App\Services\ProjectTask\Renderers;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class StatusChangeRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'sync_alt';
    }

    public function getLabel(): string
    {
        return 'تغییر وضعیت';
    }

    public function getBody(Reply $reply): string
    {
        $payload = $reply->payload ?? [];
        $from = $this->label($payload['from'] ?? null);
        $to = $this->label($payload['to'] ?? null);

        return "وضعیت از «{$from}» به «{$to}» تغییر کرد.";
    }

    private function label(?string $value): string
    {
        return TaskStatus::tryFrom((string) $value)?->getLabel() ?? 'نامشخص';
    }
}
