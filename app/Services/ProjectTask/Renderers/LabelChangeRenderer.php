<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class LabelChangeRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'label';
    }

    public function getLabel(): string
    {
        return 'تغییر برچسب‌ها';
    }

    public function getBody(Reply $reply): string
    {
        $payload = $reply->payload ?? [];
        $added = collect($payload['added'] ?? []);
        $removed = collect($payload['removed'] ?? []);

        $parts = [];
        if ($added->isNotEmpty()) {
            $parts[] = 'برچسب افزوده شد: ' . $added->implode('، ');
        }
        if ($removed->isNotEmpty()) {
            $parts[] = 'برچسب حذف شد: ' . $removed->implode('، ');
        }

        return $parts === [] ? 'برچسب‌های وظیفه تغییر کرد.' : implode(' — ', $parts);
    }
}
