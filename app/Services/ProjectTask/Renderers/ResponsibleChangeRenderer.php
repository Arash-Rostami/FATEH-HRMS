<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Models\User;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class ResponsibleChangeRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'how_to_reg';
    }

    public function getLabel(): string
    {
        return 'تغییر جوابگو';
    }

    public function getBody(Reply $reply): string
    {
        $to = $this->userName($reply->payload['to'] ?? null);

        return $to !== null
            ? "جوابگوی وظیفه به «{$to}» تغییر کرد."
            : 'جوابگوی وظیفه حذف شد.';
    }

    private function userName(?int $userId): ?string
    {
        return $userId ? User::whereKey($userId)->value('name') : null;
    }
}
