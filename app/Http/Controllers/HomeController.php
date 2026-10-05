<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard based on user role.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $role = auth()->user()->role;

        // NEW: Send both 'admin' and 'forms_admin' to the main dashboard
        if ($role === 'admin' || $role === 'forms_admin') {
            return redirect()->route('dashboard');
        } 
        
        // Send Approvers and Chief of Hospital to their restricted dashboard
        elseif ($role === 'approver' || $role === 'coh') {
            return redirect('/approver/dashboard');
        }
        
        // Send Superadmin to the User Management control panel
        elseif ($role === 'superadmin') {
            return redirect()->route('superadmin.users');
        }

        // If the role doesn't match anything above, block them.
        abort(403, 'YOU DO NOT HAVE PERMISSION TO ACCESS THIS PAGE.');
    }
}