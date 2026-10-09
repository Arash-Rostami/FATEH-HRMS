<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

class IntegerList implements CastsAttributes, ComparesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_map('intval', array_filter($decoded, 'is_scalar'))) : [];
    }

    public function compare(Model $model, string $key, mixed $value, mixed $firstValue): bool
    {
        return $this->normalize($value) === $this->normalize($firstValue);
    }

    private function normalize(mixed $value): ?array
    {
        if ($value === null) return null;

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? array_values(array_map('intval', array_filter($decoded, 'is_scalar'))) : [];
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(array_values(array_unique(array_map('intval', array_filter((array) $value, 'is_scalar')))));
    }
}
