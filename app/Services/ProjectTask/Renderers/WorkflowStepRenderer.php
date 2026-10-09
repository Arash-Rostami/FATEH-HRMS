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
            'cancelled' => ($reply->payload['reason'] ?? null) === 'project_deleted' ? 'چرخه به دلیل حذف پروژه لغو شد' : "چرخه «{$cycle}» لغو شد.",
            'restored' => "چرخه «{$cycle}» پس از بازیابی پروژه به پیش‌نویس بازگشت.",
            'reassigned' => "مسئولان مرحله «{$label}» در چرخه «{$cycle}» تغییر کرد.",
            'task_detached' => "وظیفه مرحله «{$label}» از چرخه «{$cycle}» جدا شد.",
            default => "تغییری در چرخه «{$cycle}» ثبت شد.",
        };

        return $note ? "{$body} یادداشت: {$note}" : $body;
    }
}
