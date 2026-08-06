<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    // ==========================================
    // USER MANAGEMENT
    // ==========================================
    public function userManagement()
    {
        $users = User::orderBy('name')->get();
        return view('superadmin.users', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Log the creation
        ActivityLog::log('User Management', "Created new user: {$request->name}");

        return redirect()->back()->with('success', 'User added successfully!');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        // Log the update
        ActivityLog::log('User Management', "Updated user details for: {$user->name}");

        return redirect()->back()->with('success', 'User updated successfully!');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if (auth()->id() == $id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }
        
        $userName = $user->name; // Save name for the log before deleting
        $user->delete();

        // Log the deletion
        ActivityLog::log('User Management', "Deleted user: {$userName}");

        return redirect()->back()->with('success', 'User deleted.');
    }

    // ==========================================
    // ACTIVITY LOGS
    // ==========================================
    public function activityLogs()
    {
        $logs = ActivityLog::with('user')->latest()->paginate(50);
        return view('superadmin.logs', compact('logs'));
    }

    // ==========================================
    // MASTER DATA (Departments & Categories)
    // ==========================================
    public function masterData()
    {
        $departments = Department::orderBy('group_name')->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        return view('superadmin.master-data', compact('departments', 'categories'));
    }

    public function addDepartment(Request $request)
    {
        Department::create($request->all());
        ActivityLog::log('System Setting', "Added new department: {$request->name}");
        return redirect()->back()->with('success', 'Department added successfully!');
    }

    public function updateDepartment(Request $request, $id)
    {
        $dept = Department::findOrFail($id);
        $dept->update([
            'name' => $request->name,
            'head_name' => $request->head_name,
            'group_name' => $request->group_name
        ]);
        ActivityLog::log('System Setting', "Updated department: {$dept->name}");
        return redirect()->back()->with('success', 'Department updated successfully!');
    }

    public function deleteDepartment($id)
    {
        $dept = Department::findOrFail($id);
        ActivityLog::log('System Setting', "Deleted department: {$dept->name}");
        $dept->delete();
        return redirect()->back()->with('success', 'Department removed.');
    }

    public function addCategory(Request $request)
    {
        Category::create($request->all());
        ActivityLog::log('System Setting', "Added new category: {$request->name}");
        return redirect()->back()->with('success', 'Category added successfully!');
    }

    public function deleteCategory($id)
    {
        $cat = Category::findOrFail($id);
        ActivityLog::log('System Setting', "Deleted category: {$cat->name}");
        $cat->delete();
        return redirect()->back()->with('success', 'Category removed.');
    }
}