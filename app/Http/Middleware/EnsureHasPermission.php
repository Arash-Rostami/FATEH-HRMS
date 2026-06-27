<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;

class EnsureHasPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        // Developers are super-admin by role — no permission row, no exclusions.
        if ($user->isDeveloper()) {
            return $next($request);
        }

        $perm = Permission::forUser($user->id);

        if (!$perm) {
            abort(403, __('resources/permission/strings.denied') ?? 'Forbidden');
        }

        if (!$perm->is_super_admin && empty($perm->abilities)) {
            abort(403, __('resources/permission/strings.denied') ?? 'Forbidden');
        }

        return $next($request);
    }
}
