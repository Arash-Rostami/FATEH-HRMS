<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class AttachmentRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'attach_file';
    }

    public function getLabel(): string
    {
        return 'پیوست';
    }

    public function getBody(Reply $reply): string
    {
        $names = collect($reply->files ?? [])->pluck('name')->filter();

        return $names->isEmpty()
            ? 'فایلی پیوست شد.'
            : 'پیوست شد: ' . $names->implode('، ');
    }
}
