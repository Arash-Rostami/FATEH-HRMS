<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class ArchiveRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'archive';
    }

    public function getLabel(): string
    {
        return 'بایگانی';
    }

    public function getBody(Reply $reply): string
    {
        return ($reply->payload['archived'] ?? false)
            ? 'وظیفه بایگانی شد.'
            : 'وظیفه از بایگانی خارج شد.';
    }
}
