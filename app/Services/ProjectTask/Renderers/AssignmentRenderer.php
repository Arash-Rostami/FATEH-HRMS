<?php

namespace App\Services\ProjectTask\Renderers;

use App\Models\Department;
use App\Models\Reply;
use App\Models\User;
use App\Services\ProjectTask\Contracts\ActivityLogRenderer;

class AssignmentRenderer implements ActivityLogRenderer
{
    private static array $nameCache = [];

    public function getIcon(Reply $reply): string
    {
        return 'person_add';
    }

    public function getLabel(): string
    {
        return 'واگذاری';
    }

    public function getBody(Reply $reply): string
    {
        $payload = $reply->payload ?? [];
        $audienceKeys = ['added', 'removed', 'added_departments', 'removed_departments'];

        return array_intersect_key($payload, array_flip($audienceKeys)) !== []
            ? $this->audienceBody($payload)
            : $this->taskAssignmentBody($payload);
    }

    private function taskAssignmentBody(array $payload): string
    {
        $to = $this->userName($payload['to'] ?? null);

        return $to !== null
            ? "مسئولیت وظیفه به «{$to}» واگذار شد."
            : 'مسئول وظیفه حذف شد.';
    }

    private function audienceBody(array $payload): string
    {
        $added = $this->userNames($payload['added'] ?? []);
        $removed = $this->userNames($payload['removed'] ?? []);
        $addedDepartments = $this->departmentNames($payload['added_departments'] ?? []);
        $removedDepartments = $this->departmentNames($payload['removed_departments'] ?? []);

        $parts = [];
        if ($added->isNotEmpty()) {
            $parts[] = 'عضو افزوده شد: ' . $added->implode('، ');
        }
        if ($removed->isNotEmpty()) {
            $parts[] = 'عضو حذف شد: ' . $removed->implode('، ');
        }
        if ($addedDepartments->isNotEmpty()) {
            $parts[] = 'دپارتمان افزوده شد: ' . $addedDepartments->implode('، ');
        }
        if ($removedDepartments->isNotEmpty()) {
            $parts[] = 'دپارتمان حذف شد: ' . $removedDepartments->implode('، ');
        }

        return $parts === [] ? 'دسترسی پروژه تغییر کرد.' : implode(' — ', $parts);
    }

    private function departmentNames(array $codes): \Illuminate\Support\Collection
    {
        if (empty($codes)) {
            return collect();
        }

        return Department::getCachedModels()->only($codes)->map(fn($d) => $d->displayLabel())->values();
    }

    private function userName(?int $userId): ?string
    {
        return $userId ? $this->userNames([$userId])->first() : null;
    }

    private function userNames(array $userIds): \Illuminate\Support\Collection
    {
        $missing = array_diff($userIds, array_keys(self::$nameCache));

        if ($missing !== []) {
            User::whereIn('id', $missing)->pluck('name', 'id')->each(
                fn($name, $id) => self::$nameCache[$id] = $name
            );
        }

        return collect($userIds)->map(fn($id) => self::$nameCache[$id] ?? null)->filter()->values();
    }
}
