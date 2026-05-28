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

        $perm = Permission::forUser($user->id);
        if (!$perm || !$perm->can($module, 'view')) abort(403);

        return $next($request);
    }
}
