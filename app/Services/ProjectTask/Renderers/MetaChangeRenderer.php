<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class MetaChangeRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'database';
    }

    public function getLabel(): string
    {
        return 'تغییر دیتای سفارشی';
    }

    public function getBody(Reply $reply): string
    {
        $payload = $reply->payload ?? [];

        $parts = [];
        if (($payload['added'] ?? []) !== []) {
            $parts[] = 'افزوده شد: ' . implode('، ', $payload['added']);
        }
        if (($payload['removed'] ?? []) !== []) {
            $parts[] = 'حذف شد: ' . implode('، ', $payload['removed']);
        }
        if (($payload['changed'] ?? []) !== []) {
            $parts[] = 'به‌روزرسانی: ' . implode('، ', $payload['changed']);
        }

        return $parts === [] ? 'دیتای سفارشی وظیفه تغییر کرد.' : implode(' — ', $parts);
    }
}