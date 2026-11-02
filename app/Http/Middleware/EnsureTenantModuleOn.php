<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantModuleOn
{
    public function handle(Request $request, Closure $next, string $key): mixed
    {
        abort_if(tenantOff($key), 404);

        return $next($request);
    }
}
