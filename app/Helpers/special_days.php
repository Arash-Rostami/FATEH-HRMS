<?php

use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

if (!function_exists('isSpecialDay')) {
    function isSpecialDay($type)
    {
        $user = Auth::user();
        if (!$user) return false;

        $cacheKey = $type . $user->id;
        // Check cache first to avoid re-showing
        if (cache()->has($cacheKey)) return false;

        $date = null;
        if ($type === 'birthdate' && $user->profile && $user->profile->birthdate) {
            $date = $user->profile->birthdate; // Assuming Carbon instance
        } elseif ($type === 'start_date' && $user->profile && $user->profile->start_date) {
            $date = $user->profile->start_date; // Assuming Carbon instance
        }

        if (!$date) return false;

        // Compare using Jalalian for accurate Persian date matching if needed, or standard Carbon
        // Here using standard Carbon month/day check
        return $date->format('m-d') === now()->format('m-d');
    }
}
