<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PettyCashTransaction;
use App\Models\Supply;
use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class PettyCashController extends Controller
{
    public function index()
    {
        $transactions = PettyCashTransaction::with(['processor', 'supply'])->orderBy('created_at', 'desc')->get();
        
        // Calculate current running balance
        $totalReplenished = $transactions->where('type', 'Replenishment')->sum('amount');
        $totalExpenses = $transactions->where('type', 'Expense')->sum('amount');
        $balance = $totalReplenished - $totalExpenses;

        $supplies = Supply::orderBy('name', 'asc')->get();
        $categories = Category::orderBy('name')->get(); // Fetch categories for the New Item dropdown

        return view('superadmin.petty_cash', compact('transactions', 'balance', 'supplies', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:Replenishment,Expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'receipt_reference' => 'nullable|string|max:255',
        ]);

        $supplyId = null;
        $invMode = $request->input('inventory_mode', 'none');
        $qty = (int) $request->input('quantity', 0);

        // SMART INTEGRATION: Handle dynamic inventory linking
        if ($request->input('type') === 'Expense' && $invMode !== 'none' && $qty > 0) {
            
            if ($invMode === 'existing' && $request->filled('supply_id')) {
                
                // Add stock to an existing item
                $supply = Supply::findOrFail($request->input('supply_id'));
                $supply->quantity += $qty;
                $supply->save();
                
                $supplyId = $supply->id;
                ActivityLog::log('Inventory', "Purchased {$qty} {$supply->unit} of {$supply->name} via Petty Cash.");
                
            } 
            elseif ($invMode === 'new' && $request->filled('new_item_name')) {
                
                // Bypass fillable restrictions by manually assigning exact database columns
                $supply = new Supply();
                $supply->name = $request->input('new_item_name');
                $supply->category = $request->input('new_item_category') ?: 'UNCATEGORIZED'; // Fallback if they forget to pick a category
                $supply->description = 'Purchased via Petty Cash';
                $supply->quantity = $qty;
                $supply->unit = 'pcs'; 
                $supply->reorder_level = 10;
                $supply->is_visible = 1;
                $supply->supplier = 'Petty Cash Purchase'; 
                $supply->date_delivered = \Carbon\Carbon::now();
                $supply->save();
                
                $supplyId = $supply->id;
                ActivityLog::log('Inventory', "Registered and purchased new item: {$supply->name} (Qty: {$supply->quantity}) via Petty Cash.");
                
            }
        }

        // Save the main Ledger transaction
        PettyCashTransaction::create([
            'type' => $request->input('type'),
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'receipt_reference' => $request->input('receipt_reference'),
            'supply_id' => $supplyId, // Links the inventory item if created/updated
            'processed_by' => Auth::id(),
        ]);

        // Log the cash movement
        $logDesc = $request->input('type') === 'Replenishment' 
            ? "Replenished Petty Cash by ₱" . number_format($request->input('amount'), 2)
            : "Recorded Petty Cash expense: ₱" . number_format($request->input('amount'), 2) . " for " . $request->input('description');
        ActivityLog::log('Petty Cash', $logDesc);

        return redirect()->back()->with('success', 'Petty cash transaction recorded successfully!');
    }

    public function printReceipt($id)
    {
        $transaction = PettyCashTransaction::with(['processor', 'supply'])->findOrFail($id);
        return view('superadmin.print_petty_cash', compact('transaction'));
    }
}