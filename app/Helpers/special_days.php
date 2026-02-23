<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('isSpecialDay')) {
    function isSpecialDay($type)
    {
        $user = Auth::user();
        if (!$user) return false;

        $date = null;
        if ($type === 'birthdate' && $user->profile && $user->profile->birthdate) {
            $date = $user->profile->birthdate;
        } elseif ($type === 'start_date' && $user->profile && $user->profile->start_date) {
            $date = $user->profile->start_date;
        }

        if (!$date) return false;

        $cacheKey = $type . $user->id;
        if (cache()->has($cacheKey)) return false;

        // Simple check for day/month match (assuming Carbon/DateTime objects)
        return $date->format('m-d') === now()->format('m-d');
    }
}
