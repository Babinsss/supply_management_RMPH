<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    public function userManagement()
    {
        // Fetch all users except the currently logged-in superadmin (to prevent self-lockout)
        $users = User::where('id', '!=', auth()->id())->orderBy('name', 'asc')->get();
        
        return view('superadmin.users', compact('users'));
    }

    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'role' => 'required|in:user,admin,approver,superadmin'
        ]);

        $user->role = $request->role;
        $user->save();

        return redirect()->back()->with('success', "Role updated successfully for {$user->name}.");
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Default reset password for hospital staff
        $defaultPassword = 'rmph' . date('Y'); // e.g., rmph2026
        
        $user->password = Hash::make($defaultPassword);
        $user->save();

        return redirect()->back()->with('success', "Password for {$user->name} has been reset to: {$defaultPassword}");
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->back()->with('success', 'User account permanently deleted.');
    }
    public function activityLogs()
    {
        // Fetch logs, latest first, and include the user data
        $logs = \App\Models\ActivityLog::with('user')->latest()->paginate(50);
        
        return view('superadmin.logs', compact('logs'));
    }
    // --- MASTER DATA MANAGEMENT ---

    public function masterData()
    {
        $departments = \App\Models\Department::orderBy('group_name')->orderBy('name')->get();
        $categories = \App\Models\Category::orderBy('name')->get();
        
        return view('superadmin.master-data', compact('departments', 'categories'));
    }

    public function addDepartment(\Illuminate\Http\Request $request)
    {
        \App\Models\Department::create($request->all());
        \App\Models\ActivityLog::log('System Setting', "Added new department: {$request->name}");
        return redirect()->back()->with('success', 'Department added successfully!');
    }

    public function addCategory(\Illuminate\Http\Request $request)
    {
        \App\Models\Category::create($request->all());
        \App\Models\ActivityLog::log('System Setting', "Added new category: {$request->name}");
        return redirect()->back()->with('success', 'Category added successfully!');
    }

    public function deleteDepartment($id)
    {
        $dept = \App\Models\Department::findOrFail($id);
        \App\Models\ActivityLog::log('System Setting', "Deleted department: {$dept->name}");
        $dept->delete();
        return redirect()->back()->with('success', 'Department removed.');
    }

    public function deleteCategory($id)
    {
        $cat = \App\Models\Category::findOrFail($id);
        \App\Models\ActivityLog::log('System Setting', "Deleted category: {$cat->name}");
        $cat->delete();
        return redirect()->back()->with('success', 'Category removed.');
    }
}