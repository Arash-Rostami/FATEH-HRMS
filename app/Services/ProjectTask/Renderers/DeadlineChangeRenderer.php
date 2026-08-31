<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class DeadlineChangeRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'event';
    }

    public function getLabel(): string
    {
        return 'تغییر مهلت';
    }

    public function getBody(Reply $reply): string
    {
        $payload = $reply->payload ?? [];
        $from = $this->label($payload['from'] ?? null);
        $to = $this->label($payload['to'] ?? null);

        if ($to === null) {
            return 'مهلت انجام حذف شد.';
        }

        return $from === null
            ? "مهلت انجام «{$to}» تعیین شد."
            : "مهلت انجام از «{$from}» به «{$to}» تغییر کرد.";
    }

    private function label(?string $value): ?string
    {
        return $value ? toJalali($value, 'j F Y') : null;
    }
}
