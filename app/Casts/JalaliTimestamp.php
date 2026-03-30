<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class JalaliTimestamp implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return self::toJalali($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        return Carbon::parse($value);
    }

    public static function toJalali(string|Carbon $date): string
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return Jalalian::fromCarbon($date)->format('Y/m/d H:i');
    }
}
