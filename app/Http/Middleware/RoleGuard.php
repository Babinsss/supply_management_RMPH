<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleGuard
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        // 1. Check if they are logged in at all
        if (!Auth::check()) {
            return redirect('/login')->with('danger', 'Please log in to access this page.');
        }

        // 2. Check if their role matches the required role for this route
        if (Auth::user()->role !== $role) {
            // If they are an approver trying to access admin, kick them back to their own dashboard
            if (Auth::user()->role === 'approver') {
                return redirect('/approver-dashboard')->with('danger', 'Unauthorized access.');
            }
            // If they are admin trying to access approver, kick them back
            if (Auth::user()->role === 'admin') {
                return redirect('/dashboard')->with('danger', 'Unauthorized access.');
            }
            
            // Default fallback
            abort(403, 'You do not have permission to access this page.');
        }

        // 3. If they pass the checks, let them through!
        return $next($request);
    }
}