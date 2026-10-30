<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;

trait HasExtraPreferences
{
    public function getExtraValue(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->extra ?? [], $key, $default);
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->getExtraValue("preferences.{$key}", $default);
    }

    public function setExtraValue(string $key, mixed $value): void
    {
        $extra = $this->extra ?? [];
        Arr::set($extra, $key, $value);
        $this->extra = $extra;
    }

    protected function extra(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->decodeExtra($value),
            set: fn($value) => $this->mergeExtra($value),
        );
    }

    private function decodeExtra($value): array
    {
        $raw = is_array($value) ? $value : json_decode($value ?? '[]', true);

        return is_array($raw) ? $raw : [];
    }

    private function decodeStoredExtra(): array
    {
        if (!isset($this->attributes['extra'])) {
            return [];
        }

        $decoded = json_decode($this->attributes['extra'], true);

        return is_array($decoded) ? $decoded : [];
    }

    private function mergeExtra($value): string
    {
        $incoming = is_array($value) ? $value : json_decode($value ?? '[]', true);
        if (!is_array($incoming)) {
            $incoming = [];
        }

        $existing = $this->decodeStoredExtra();
        $result = array_filter($existing, fn($v) => is_array($v));

        $reserved = ['preferences', 'admin'];

        if (array_key_exists('preferences', $incoming) && is_array($incoming['preferences'] ?? null)) {
            $result['preferences'] = array_merge($result['preferences'] ?? [], $incoming['preferences']);

            if (array_key_exists('admin', $incoming)) {
                $result['admin'] = is_array($incoming['admin']) ? $incoming['admin'] : [];
            }

            foreach ($incoming as $k => $v) {
                if (!in_array($k, $reserved, true)) {
                    $result['admin'][$k] = $v;
                }
            }
        } else {
            $adminOverride = array_key_exists('admin', $incoming) && is_array($incoming['admin'])
                ? $incoming['admin']
                : [];

            unset($incoming['preferences'], $incoming['admin']);

            $result['admin'] = array_merge($adminOverride, $incoming);
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}