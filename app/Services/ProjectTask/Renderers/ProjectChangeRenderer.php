<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Project;
use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class ProjectChangeRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'drive_file_move';
    }

    public function getLabel(): string
    {
        return 'تغییر پروژه';
    }

    public function getBody(Reply $reply): string
    {
        $payload = $reply->payload ?? [];
        $from = $this->projectName($payload['from'] ?? null);
        $to = $this->projectName($payload['to'] ?? null);

        if ($to === null) {
            return $from !== null
                ? "وظیفه از پروژه «{$from}» خارج شد."
                : 'وظیفه از پروژه خارج شد.';
        }

        return $from === null
            ? "وظیفه به پروژه «{$to}» منتقل شد."
            : "وظیفه از پروژه «{$from}» به «{$to}» منتقل شد.";
    }

    private function projectName(?int $projectId): ?string
    {
        return $projectId ? Project::query()->whereKey($projectId)->value('name') : null;
    }
}
