<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleGuard
{
    public function handle(Request $request, Closure $next, $role)
    {
        // 1. Check if the user is logged in
        if (!auth()->check()) {
            abort(403, 'YOU DO NOT HAVE PERMISSION TO ACCESS THIS PAGE.');
        }

        // 2. SUPERADMIN OVERRIDE: Let the superadmin access everything!
        if (auth()->user()->role === 'superadmin') {
            return $next($request);
        }

        // 3. Normal Role Check: Does their role match the required route role?
        if (auth()->user()->role !== $role) {
            abort(403, 'YOU DO NOT HAVE PERMISSION TO ACCESS THIS PAGE.');
        }

        return $next($request);
    }
}