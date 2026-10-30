<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasBookingRows
{
    public function bookingRows(): array
    {
        $raw = $this->attributes['booking'] ?? [];
        $raw = is_array($raw) ? $raw : json_decode($raw ?? '[]', true);

        return is_array($raw) ? $raw : [];
    }

    public function bookingMax(string $type): ?int
    {
        return $this->bookingRowMax($type) ?? $this->bookingRowMax('all');
    }

    private function bookingRowMax(string $type): ?int
    {
        foreach ($this->bookingRows() as $row) {
            if (is_array($row) && ($row['key'] ?? null) === $type && filled($row['max'] ?? null)) {
                return max(1, (int) $row['max']);
            }
        }

        return null;
    }

    protected function booking(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $raw = is_array($value) ? $value : json_decode($value ?? '[]', true);
                if (!is_array($raw)) return [];

                $permissions = [];
                foreach ($raw as $k => $v) {
                    if (is_array($v)) {
                        if (array_key_exists('key', $v) && array_key_exists('value', $v)) {
                            $permissions[$v['key']] = $v['value'];
                        }
                        continue;
                    }
                    $permissions[$k] = $v;
                }

                return $permissions;
            },
            set: fn($value) => is_array($value) ? json_encode($value) : $value
        );
    }
}