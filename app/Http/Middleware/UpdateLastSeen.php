<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            $cacheKey = 'last_seen:' . $user->id;

            if (!Cache::has($cacheKey)) {
                $user->touchLastSeen();
                Cache::put($cacheKey, true, now()->addMinutes(5));
            }
        }

        return $next($request);
    }
}
