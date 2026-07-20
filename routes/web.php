<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupplyController;

/*
|--------------------------------------------------------------------------
| ICT SECTION (ADMIN) ROUTES
|--------------------------------------------------------------------------
| Locked behind 'admin' role middleware. Only ICT can issue and edit.
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    
    // Dashboard & Item Operations
    Route::get('/', [SupplyController::class, 'index']);
    Route::get('/dashboard', [SupplyController::class, 'dashboard'])->name('dashboard');
    Route::post('/add', [SupplyController::class, 'addItem']);
    Route::post('/update/{id}', [SupplyController::class, 'updateStock']);
    Route::get('/delete/{id}', [SupplyController::class, 'deleteItem']);

    // Processing Batches
    Route::post('/process-batch/{batch_id}/approve', [SupplyController::class, 'approveBatch']);
    Route::get('/process-batch/{batch_id}/deny', [SupplyController::class, 'denyBatch']);

    // Stockcard and Printing Actions
    Route::get('/stockcard/{item_id}', [SupplyController::class, 'stockcard']);
    Route::get('/print-bulk/{batch_id}', [SupplyController::class, 'printBulk']);

    // Inventory and Export Operations
    Route::get('/inventory', [SupplyController::class, 'inventory']);
    Route::put('/inventory/update/{id}', [SupplyController::class, 'update']);
    Route::get('/export-stockcard/{id}', [SupplyController::class, 'exportExcel']);
    Route::get('/export-inventory', [SupplyController::class, 'exportInventoryExcel']);

    // Real-Time Poller API
    Route::get('/api/pending-count', [SupplyController::class, 'pendingCountApi']);
});

/*
|--------------------------------------------------------------------------
| QMO APPROVER ROUTES
|--------------------------------------------------------------------------
| Locked behind 'approver' role middleware. Read/Monitor only.
*/
Route::middleware(['auth', 'role:approver'])->group(function () {
    Route::get('/approver/dashboard', [SupplyController::class, 'approverDashboard']);
    Route::get('/approver/inventory', [SupplyController::class, 'approverInventory']);
});

/*
|--------------------------------------------------------------------------
| DEPARTMENT FRONT-FACING PORTAL
|--------------------------------------------------------------------------
| Accessible to hospital staff to submit their requisition carts.
*/
Route::get('/portal', [SupplyController::class, 'departmentPortal'])->name('portal');
Route::post('/submit-request', [SupplyController::class, 'submitRequest']);

// This gives the 'auth' middleware a place to redirect unauthenticated users
Route::get('/login', function () {
    return "The Login Page goes here! (We need to build this next)";
})->name('login');
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
