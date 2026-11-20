<?php

namespace App\Traits\Livewire;

use App\Models\User;
use Illuminate\Support\Collection;

trait BulkSelectsPeople
{
    public function bulkSelectPeople(string $property, string $kind, ?string $deptValue = null): void
    {
        $dept = $kind === 'all' || $deptValue === '' ? null : $deptValue;

        if (!in_array($kind, ['all', 'department', 'none'], true)
            || ($kind === 'department' && $dept === null)
            || !in_array($property, $this->bulkSelectTargets(), true)
            || !$this->canBulkSelect($property)) {
            return;
        }

        $current = data_get($this, $property);
        $current = $this->bulkNormalizeIds($current instanceof Collection ? $current->all() : $current);

        if ($kind === 'none') {
            data_set($this, $property, $dept === null ? [] : array_values(array_diff($current, $this->bulkNormalizeIds($this->bulkPeoplePool($property, $dept)))));

            return;
        }

        $selected = array_values(array_unique(array_merge($current, $this->bulkNormalizeIds($this->bulkPeoplePool($property, $dept)))));
        $limit = $this->bulkSelectLimit($property);

        if ($limit !== null && count($selected) > $limit) {
            $selected = array_slice($selected, 0, $limit);
            $this->dispatch('toast', message: 'حداکثر ' . convertToPersian((string) $limit) . ' نفر قابل انتخاب است.', type: 'info');
        }

        data_set($this, $property, $selected);
    }

    abstract protected function bulkSelectTargets(): array;

    protected function bulkPeoplePool(string $property, ?string $dept = null): array
    {
        return $this->bulkDefaultPeoplePool($dept);
    }

    protected function canBulkSelect(string $property): bool
    {
        return true;
    }

    protected function bulkSelectLimit(string $property): ?int
    {
        return null;
    }

    protected function bulkDefaultPeoplePool(?string $dept = null, ?int $exceptId = null): array
    {
        $depts = User::getCachedActiveDeptMap();
        $exceptId ??= (int) auth()->id();

        return User::getCachedActiveOptions()->keys()
            ->reject(fn ($id) => (int) $id === $exceptId)
            ->when($dept !== null, fn ($ids) => $ids->filter(fn ($id) => ($depts[(int) $id] ?? '') === $dept))
            ->map(fn ($id) => (int) $id)->values()->all();
    }

    private function bulkNormalizeIds(mixed $ids): array
    {
        return is_array($ids)
            ? array_values(array_unique(array_filter(array_map('intval', array_filter($ids, 'is_numeric')), fn (int $id) => $id > 0)))
            : [];
    }
}
