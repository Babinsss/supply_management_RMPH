<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        // 1. If Superadmin, send them to the User Management Dashboard
        if ($user->role === 'superadmin') {
            return redirect('/superadmin/users');
        } 
        
        // 2. If ICT Admin, send them to the main Admin Dashboard
        elseif ($user->role === 'admin') {
            return redirect('/dashboard'); 
        } 
        
        // 3. If QMO Approver OR Chief of Hospital, send them to the Approver Dashboard
        elseif ($user->role === 'approver' || $user->role === 'coh') {
            return redirect('/approver/dashboard');
        }

        // 4. If standard user, send them to the Requisition Portal
        return redirect('/portal');
    }
}