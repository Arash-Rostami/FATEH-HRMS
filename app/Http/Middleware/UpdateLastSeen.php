<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            if (!$user->isActive()) {
                Auth::guard('web')->logout();
                return redirect('/login');
            }

            $cacheKey = 'last_seen:' . $user->id;

            if (!Cache::has($cacheKey)) {
                $user->touchLastSeen();
                Cache::put($cacheKey, true, now()->addMinutes(User::TOUCH_MINUTES));
            }
        }

        return $next($request);
    }
}
