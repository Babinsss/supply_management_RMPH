<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request; // <--- This is required for the redirect to work

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * The user has been authenticated. Check their role and send them to the right dashboard.
     */
    protected function authenticated(Request $request, $user)
    {
        // 1. If Superadmin, send them to the User Management Dashboard
        if ($user->role === 'superadmin') {
            return redirect('/superadmin/users');
        } 
        
        // 2. If ICT Admin, send them to the main Admin Dashboard
        elseif ($user->role === 'admin') {
            return redirect('/dashboard'); // Or '/' if that's your main admin view
        } 
        
        // 3. If QMO Approver, send them to the Approver Dashboard
        elseif ($user->role === 'approver') {
            return redirect('/approver/dashboard');
        }

        // 4. If standard user, send them to the Requisition Portal
        return redirect('/portal');
    }
}