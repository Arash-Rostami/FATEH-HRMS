<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;

class EnsureUserModulePermission
{
    public function handle(Request $request, Closure $next, string $module): mixed
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        // Developers are super-admin by role — every module, no permission row.
        if ($user->isDeveloper()) {
            return $next($request);
        }

        $perm = Permission::forUser($user->id);
        if (!$perm || !$perm->can($module, 'view')) abort(403);

        return $next($request);
    }
}
