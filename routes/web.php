<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupplyController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| SHARED ROUTES (Accessible by BOTH Admin and Approver)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/stockcard/{item_id}', [SupplyController::class, 'stockcard']);
});

/*
|--------------------------------------------------------------------------
| ICT SECTION (ADMIN) ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [SupplyController::class, 'index']);
    Route::get('/dashboard', [SupplyController::class, 'dashboard'])->name('dashboard');
    Route::post('/add', [SupplyController::class, 'addItem']);
    Route::post('/update/{id}', [SupplyController::class, 'updateStock']);
    Route::get('/delete/{id}', [SupplyController::class, 'deleteItem']);

    Route::post('/process-batch/{batch_id}/approve', [SupplyController::class, 'approveBatch']);
    Route::get('/process-batch/{batch_id}/deny', [SupplyController::class, 'denyBatch']);

    Route::get('/print-bulk/{batch_id}', [SupplyController::class, 'printBulk']);
    Route::get('/print-inventory', [SupplyController::class, 'printInventory']);

    Route::get('/inventory', [SupplyController::class, 'inventory']);
    Route::put('/inventory/update/{id}', [SupplyController::class, 'update']);
    Route::post('/inventory/toggle-visibility/{id}', [SupplyController::class, 'toggleVisibility']);
    Route::get('/export-stockcard/{id}', [SupplyController::class, 'exportExcel']);
    Route::get('/export-inventory', [SupplyController::class, 'exportInventoryExcel']);

    Route::get('/api/pending-count', [SupplyController::class, 'pendingCountApi']);
});

/*
|--------------------------------------------------------------------------
| QMO APPROVER ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:approver'])->group(function () {
    Route::get('/approver/dashboard', [SupplyController::class, 'approverDashboard']);
    Route::get('/approver/inventory', [SupplyController::class, 'approverInventory']);
});

/*
|--------------------------------------------------------------------------
| SUPERADMIN ROUTES (System Management)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->group(function () {
    // User Management
    Route::get('/users', [App\Http\Controllers\SuperAdminController::class, 'userManagement'])->name('superadmin.users');
    Route::post('/users/add', [App\Http\Controllers\SuperAdminController::class, 'storeUser']);
    Route::post('/users/update/{id}', [App\Http\Controllers\SuperAdminController::class, 'updateUser']);
    Route::get('/users/delete/{id}', [App\Http\Controllers\SuperAdminController::class, 'deleteUser']);
    Route::get('/logs', [App\Http\Controllers\SuperAdminController::class, 'activityLogs'])->name('superadmin.logs');
    
    // Master Data (Departments & Categories)
    Route::get('/master-data', [App\Http\Controllers\SuperAdminController::class, 'masterData'])->name('superadmin.master_data');
    Route::post('/master-data/department', [App\Http\Controllers\SuperAdminController::class, 'addDepartment']);
    Route::post('/master-data/department/update/{id}', [App\Http\Controllers\SuperAdminController::class, 'updateDepartment']);
    Route::post('/master-data/category', [App\Http\Controllers\SuperAdminController::class, 'addCategory']);
    Route::get('/master-data/department/delete/{id}', [App\Http\Controllers\SuperAdminController::class, 'deleteDepartment']);
    Route::get('/master-data/category/delete/{id}', [App\Http\Controllers\SuperAdminController::class, 'deleteCategory']);
});

/*
|--------------------------------------------------------------------------
| DEPARTMENT FRONT-FACING PORTAL
|--------------------------------------------------------------------------
*/
Route::get('/portal', [SupplyController::class, 'departmentPortal'])->name('portal');
Route::post('/submit-request', [SupplyController::class, 'submitRequest']);

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');