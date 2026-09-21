<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class WorkflowStepRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'conversion_path';
    }

    public function getLabel(): string
    {
        return 'چرخه کاری';
    }

    public function getBody(Reply $reply): string
    {
        $cycle = $reply->payload['cycle'] ?? '';
        $label = $reply->payload['label'] ?? '';
        $note = $reply->payload['note'] ?? null;

        $body = match ($reply->payload['event'] ?? null) {
            'started' => "چرخه «{$cycle}» با مرحله «{$label}» آغاز شد.",
            'advanced' => "مرحله «{$label}» در چرخه «{$cycle}» تکمیل شد.",
            'rejected' => "مرحله «{$label}» در چرخه «{$cycle}» رد شد.",
            'completed' => "چرخه «{$cycle}» تکمیل شد.",
            'cancelled' => "چرخه «{$cycle}» لغو شد.",
            default => "تغییری در چرخه «{$cycle}» ثبت شد.",
        };

        return $note ? "{$body} یادداشت: {$note}" : $body;
    }
}
