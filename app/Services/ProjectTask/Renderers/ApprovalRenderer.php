<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class ApprovalRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'verified_user';
    }

    public function getLabel(): string
    {
        return 'تأیید مدیر پروژه';
    }

    public function getBody(Reply $reply): string
    {
        return ($reply->payload['approved'] ?? false)
            ? 'وظیفه توسط مدیر پروژه تأیید شد.'
            : 'تأیید وظیفه برداشته شد.';
    }
}