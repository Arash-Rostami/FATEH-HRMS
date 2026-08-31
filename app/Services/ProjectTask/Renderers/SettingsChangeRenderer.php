<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Reply;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class SettingsChangeRenderer implements ActivityLogRenderer
{
    private const KEY_LABELS = [
        'requires_approval' => 'نیازمند تأیید مدیر',
        'sla' => 'SLA',
        'deadline' => 'سقف مهلت',
        'custom_schema' => 'فیلدهای سفارشی',
    ];

    public function getIcon(Reply $reply): string
    {
        return 'tune';
    }

    public function getLabel(): string
    {
        return 'تغییر تنظیمات پروژه';
    }

    public function getBody(Reply $reply): string
    {
        $keys = $reply->payload['changed'] ?? [];

        $labels = array_map(
            fn(string $key) => self::KEY_LABELS[$key] ?? $key,
            $keys,
        );

        return $labels === [] ? 'تنظیمات پروژه تغییر کرد.' : implode('، ', $labels) . ' تغییر کرد.';
    }
}