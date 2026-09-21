<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupplyController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| SHARED ROUTES (Accessible by BOTH Admin and Superadmin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/stockcard/{item_id}', [SupplyController::class, 'stockcard']);
    Route::get('/print-inventory', [SupplyController::class, 'printInventory']);
    
    // Item Usage / Top Requestors Report (Shared Access)
    Route::get('/print-item-usage', [SupplyController::class, 'printItemUsage']);
    
    // Live Inventory Sync Route
    Route::get('/api/inventory/live', [SupplyController::class, 'liveInventory']); 
    
    // --- PETTY CASH ADMIN LEDGER ROUTES ---
    Route::get('/petty-cash', [App\Http\Controllers\PettyCashController::class, 'index']);
    Route::post('/petty-cash', [App\Http\Controllers\PettyCashController::class, 'store']);
    Route::get('/petty-cash/print/{id}', [App\Http\Controllers\PettyCashController::class, 'printReceipt']);
});

/*
|--------------------------------------------------------------------------
| SUPPLY SECTION (ADMIN) ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [SupplyController::class, 'index']);
    Route::get('/dashboard', [SupplyController::class, 'dashboard'])->name('dashboard');
    Route::post('/add', [SupplyController::class, 'addItem']);
    Route::post('/update/{id}', [SupplyController::class, 'updateStock']);
    Route::get('/delete/{id}', [SupplyController::class, 'deleteItem']);

    Route::post('/process-batch/{batch_id}/approve', [SupplyController::class, 'approveBatch']);
    Route::post('/process-batch/{batch_id}/deny', [SupplyController::class, 'denyBatch']);

    Route::get('/inventory', [SupplyController::class, 'inventory']);
    Route::put('/inventory/update/{id}', [SupplyController::class, 'update']);
    Route::post('/inventory/toggle-visibility/{id}', [\App\Http\Controllers\SupplyController::class, 'toggleVisibility']);
    Route::get('/export-stockcard/{id}', [SupplyController::class, 'exportExcel']);
    Route::get('/export-inventory', [SupplyController::class, 'exportInventoryExcel']);

    // Automated PR Generation
    Route::get('/generate-critical-pr', [SupplyController::class, 'generateCriticalPR']);

    // Print Routes
    Route::get('/bincard/{id}', [SupplyController::class, 'bincard']);
    Route::get('/rapc/{id}', [SupplyController::class, 'rapc']);

    Route::get('/api/pending-count', [SupplyController::class, 'pendingCountApi']);
    Route::get('/api/low-stock', [SupplyController::class, 'lowStockApi']);
});

/*
|--------------------------------------------------------------------------
| QMO & CHIEF OF HOSPITAL ROUTES (View Only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:approver,coh'])->group(function () {
    Route::get('/approver/dashboard', [SupplyController::class, 'approverDashboard']);
    Route::get('/approver/inventory', [SupplyController::class, 'approverInventory']);
});

/*
|--------------------------------------------------------------------------
| SUPERADMIN ROUTES (System Management)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/users', [App\Http\Controllers\SuperAdminController::class, 'userManagement'])->name('superadmin.users');
    Route::post('/users/add', [App\Http\Controllers\SuperAdminController::class, 'storeUser']);
    Route::post('/users/update/{id}', [App\Http\Controllers\SuperAdminController::class, 'updateUser']);
    Route::get('/users/delete/{id}', [App\Http\Controllers\SuperAdminController::class, 'deleteUser']);
    Route::get('/logs', [App\Http\Controllers\SuperAdminController::class, 'activityLogs'])->name('superadmin.logs');
    
    Route::get('/master-data', [App\Http\Controllers\SuperAdminController::class, 'masterData'])->name('superadmin.master_data');
    Route::post('/master-data/department', [App\Http\Controllers\SuperAdminController::class, 'addDepartment']);
    Route::post('/master-data/department/update/{id}', [App\Http\Controllers\SuperAdminController::class, 'updateDepartment']);
    Route::post('/master-data/category', [App\Http\Controllers\SuperAdminController::class, 'addCategory']);
    Route::get('/master-data/department/delete/{id}', [App\Http\Controllers\SuperAdminController::class, 'deleteDepartment']);
    Route::get('/master-data/category/delete/{id}', [App\Http\Controllers\SuperAdminController::class, 'deleteCategory']);
    Route::get('/ris-archive', [App\Http\Controllers\SuperAdminController::class, 'risArchive'])->name('superadmin.ris_archive');
    Route::post('/inventory/adjust/{id}', [App\Http\Controllers\SupplyController::class, 'adjustStock']);
});

/*
|--------------------------------------------------------------------------
| DEPARTMENT FRONT-FACING PORTAL
|--------------------------------------------------------------------------
*/
Route::get('/portal', [SupplyController::class, 'departmentPortal'])->name('portal');
Route::post('/submit-request', [SupplyController::class, 'submitRequest']);
Route::get('/print-bulk/{batch_id}', [SupplyController::class, 'printBulk']);
Route::post('/portal/petty-cash/print', [SupplyController::class, 'printPettyCashRequest']);

// Disables the /register route
Auth::routes(['register' => false]); 

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');