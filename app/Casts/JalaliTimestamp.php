<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class JalaliTimestamp implements CastsAttributes
{
    /**
     * Cast the given value (from the DB, usually UTC string/timestamp) to a Jalalian instance.
     * 
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Jalalian
    {
        if (is_null($value)) {
            return null;
        }

        // Convert the database UTC value to Carbon, then parse it to Jalali.
        $carbon = Carbon::parse($value);
        return Jalalian::fromCarbon($carbon);
    }

    /**
     * Prepare the given value for storage (converting Jalalian or anything back to standard UTC timestamp string).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof Jalalian) {
            return $value->toCarbon()->toDateTimeString();
        }

        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        // Assume it's a string, attempt to parse via Carbon
        return Carbon::parse($value)->toDateTimeString();
    }
}
