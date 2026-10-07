<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $role = Auth::user()->role;

        // Group all inventory admins together
        if (in_array($role, ['superadmin', 'admin', 'forms_admin'])) {
            return redirect()->route('dashboard');
        } 
        // Route QMO and Chief of Hospital to their specific dashboard
        elseif (in_array($role, ['approver', 'coh'])) {
            return redirect('/approver/dashboard');
        }

        // Fallback for normal staff (if they ever get login accounts)
        return redirect()->route('portal');
    }
}