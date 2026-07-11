<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupplyController;

// Admin Dashboard & Item Operations
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

// Department Front-Facing Portal
Route::get('/portal', [SupplyController::class, 'departmentPortal'])->name('portal');
Route::post('/submit-request', [SupplyController::class, 'submitRequest']);

// Real-Time Poller API
Route::get('/api/pending-count', [SupplyController::class, 'pendingCountApi']);
// Inventory and Export Operations
Route::get('/inventory', [SupplyController::class, 'inventory']);
Route::get('/export-stockcard/{id}', [SupplyController::class, 'exportExcel']);
Route::get('/export-inventory', [SupplyController::class, 'exportInventoryExcel']);