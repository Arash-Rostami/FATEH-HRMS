<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class CommentRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'chat_bubble';
    }

    public function getLabel(): string
    {
        return 'نظر';
    }

    public function getBody(Reply $reply): string
    {
        return (string) $reply->body;
    }
}
