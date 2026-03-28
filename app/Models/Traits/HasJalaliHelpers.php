<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

trait HasJalaliHelpers
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Jalalian
    {
        if (is_null($value)) return null;

        $carbon = Carbon::parse($value);
        return Jalalian::fromCarbon($carbon);
    }

    /**
     * Prepare the given value for storage (converting Jalalian to standard UTC timestamp string).
     *
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) return null;

        if ($value instanceof Jalalian) return $value->toCarbon()->toDateTimeString();

        if ($value instanceof Carbon) return $value->toDateTimeString();

        return Carbon::parse($value)->toDateTimeString();
    }
}
