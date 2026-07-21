<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupplyController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| SHARED ROUTES (Accessible by BOTH Admin and Approver)
|--------------------------------------------------------------------------
| Locked behind basic 'auth' middleware so only logged-in users can access.
*/
Route::middleware(['auth'])->group(function () {
    // Both ICT Admin and QMO Approver need to print stockcards
    Route::get('/stockcard/{item_id}', [SupplyController::class, 'stockcard']);
});

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

    // Printing Actions
    Route::get('/print-bulk/{batch_id}', [SupplyController::class, 'printBulk']);
    Route::get('/print-inventory', [SupplyController::class, 'printInventory']);

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

// Laravel's built-in Authentication Routes
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');