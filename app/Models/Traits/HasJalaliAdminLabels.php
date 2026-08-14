<?php

namespace App\Models\Traits;

trait HasJalaliAdminLabels
{
    public function adminDateLabel(string $column, ?string $fallback = '—'): ?string
    {
        $value = $this->getAttribute($column);

        return $value ? toJalaliSmart($value) : $fallback;
    }

    public function createdLabel(?string $fallback = '—'): ?string
    {
        return $this->adminDateLabel('created_at', $fallback);
    }

    public function updatedLabel(?string $fallback = '—'): ?string
    {
        return $this->adminDateLabel('updated_at', $fallback);
    }

    public function deletedLabel(?string $fallback = '—'): ?string
    {
        return $this->adminDateLabel('deleted_at', $fallback);
    }

    public function archivedLabel(?string $fallback = '—'): ?string
    {
        return $this->adminDateLabel('archived_at', $fallback);
    }
}