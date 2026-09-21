<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleGuard
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // 1. Check if the user is logged in
        if (!auth()->check()) {
            abort(403, 'YOU DO NOT HAVE PERMISSION TO ACCESS THIS PAGE.');
        }

        // 2. SUPERADMIN OVERRIDE: Let the superadmin access everything!
        if (auth()->user()->role === 'superadmin') {
            return $next($request);
        }

        // 3. Normal Role Check: Does their role match ANY of the required route roles?
        if (!in_array(auth()->user()->role, $roles)) {
            abort(403, 'YOU DO NOT HAVE PERMISSION TO ACCESS THIS PAGE.');
        }

        return $next($request);
    }
}