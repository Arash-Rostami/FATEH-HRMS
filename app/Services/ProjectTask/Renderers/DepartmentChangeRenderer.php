<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Department;
use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class DepartmentChangeRenderer implements ActivityLogRenderer
{
    public function getIcon(Reply $reply): string
    {
        return 'apartment';
    }

    public function getLabel(): string
    {
        return 'تغییر بخش سازمانی';
    }

    public function getBody(Reply $reply): string
    {
        $to = $this->departmentLabel($reply->payload['to'] ?? null);

        return $to !== null
            ? "بخش سازمانی وظیفه به «{$to}» تغییر کرد."
            : 'بخش سازمانی وظیفه برداشته شد.';
    }

    private function departmentLabel(?string $code): ?string
    {
        return $code ? Department::getCachedModels()->get($code)?->displayLabel() : null;
    }
}
