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
        return 'تغییر دپارتمان';
    }

    public function getBody(Reply $reply): string
    {
        $to = $this->departmentLabel($reply->payload['to'] ?? null);

        return $to !== null
            ? "دپارتمان وظیفه به «{$to}» تغییر کرد."
            : 'دپارتمان وظیفه حذف شد.';
    }

    private function departmentLabel(?string $code): ?string
    {
        return $code ? Department::getCachedModels()->get($code)?->displayLabel() : null;
    }
}
