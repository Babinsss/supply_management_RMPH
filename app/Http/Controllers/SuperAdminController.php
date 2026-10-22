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
        // FIX: Strict validation prevents malicious payload injections
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'head_name' => 'nullable|string|max:255',
            'group_name' => 'nullable|string|max:255'
        ]);

        Department::create($validated);
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
        // FIX: Strict validation ensures only expected fields are saved
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        Category::create($validated);
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
    public function risArchive()
    {
        // Fetch all requests, typically you want to see Issued/Approved ones
        $allRequests = \App\Models\DepartmentRequest::with(['supply', 'issuer'])
            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY'])
            ->where('batch_id', 'not like', 'ADJ-%')
            ->where('batch_id', 'not like', 'DEL-%')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group them by batch_id just like the main dashboard
        $requests = $allRequests->groupBy('batch_id')->map(function ($items, $batchId) {
            return [
                'batch_id' => $batchId,
                'department_name' => $items->first()->department_name,
                'requested_by' => $items->first()->requested_by,
                'status' => $items->first()->status,
                'created_at' => $items->first()->created_at,
                'items' => $items
            ];
        });

        return view('superadmin.ris_archive', compact('requests'));
    }

    public function backups()
    {
        $storagePath = storage_path('app/backups');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $files = \Illuminate\Support\Facades\File::files($storagePath);
        $backups = collect();
        $totalBytes = 0;

        foreach ($files as $file) {
            $name = $file->getFilename();
            // Skip non-sql files or hidden files
            if (!str_ends_with($name, '.sql')) continue;

            $bytes = $file->getSize();
            // Skip 0-byte or tiny error files
            if ($bytes < 1024) continue;

            $totalBytes += $bytes;
            $mtime = \Carbon\Carbon::createFromTimestamp($file->getMTime())->timezone('Asia/Manila');

            $backups->push([
                'filename' => $name,
                'size_bytes' => $bytes,
                'size_formatted' => $bytes >= 1048576 
                    ? number_format($bytes / 1048576, 2) . ' MB' 
                    : number_format($bytes / 1024, 1) . ' KB',
                'created_at' => $mtime,
                'age_days' => $mtime->diffInDays(now()->timezone('Asia/Manila')),
            ]);
        }

        $backups = $backups->sortByDesc('created_at')->values();
        $latest = $backups->first();
        $isToday = $latest && $latest['created_at']->isToday();

        $stats = [
            'count' => $backups->count(),
            'total_size' => $totalBytes >= 1048576 
                ? number_format($totalBytes / 1048576, 2) . ' MB' 
                : number_format($totalBytes / 1024, 1) . ' KB',
            'latest' => $latest,
            'is_protected' => $isToday,
        ];

        return view('superadmin.backups', compact('backups', 'stats'));
    }

    public function createBackup()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('db:backup', ['--manual' => true]);
            $output = trim(\Illuminate\Support\Facades\Artisan::output());

            return redirect()->route('superadmin.backups')
                ->with('success', 'Fresh database backup created successfully! ' . $output);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Manual backup failed: ' . $e->getMessage());
            return redirect()->route('superadmin.backups')
                ->with('danger', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    public function downloadBackup($filename)
    {
        // Security: strictly validate filename format
        if (!preg_match('/^rmph_backup_[a-zA-Z0-9_\-]+\.sql$/', $filename)) {
            abort(400, 'Invalid backup file name.');
        }

        $filePath = storage_path('app/backups/' . $filename);
        if (!file_exists($filePath)) {
            return redirect()->route('superadmin.backups')
                ->with('danger', 'The requested backup file does not exist.');
        }

        ActivityLog::log('Database Backup', "Downloaded database backup: {$filename}");

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/sql'
        ]);
    }

    public function deleteBackup($filename)
    {
        if (!preg_match('/^rmph_backup_[a-zA-Z0-9_\-]+\.sql$/', $filename)) {
            abort(400, 'Invalid backup file name.');
        }

        $filePath = storage_path('app/backups/' . $filename);
        if (file_exists($filePath)) {
            @unlink($filePath);
            ActivityLog::log('Database Backup', "Deleted backup file: {$filename}");
            return redirect()->route('superadmin.backups')
                ->with('success', "Backup file {$filename} has been deleted.");
        }

        return redirect()->route('superadmin.backups')
            ->with('danger', 'File not found.');
    }
}
