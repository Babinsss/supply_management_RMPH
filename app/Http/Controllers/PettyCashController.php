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
        // Only fetch Expense transactions since Replenishments are no longer handled here
        $transactions = PettyCashTransaction::with(['processor', 'supply'])
            ->where('type', 'Expense')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $supplies = Supply::orderBy('name', 'asc')->get();
        $categories = Category::orderBy('name')->get(); 

        return view('superadmin.petty_cash', compact('transactions', 'supplies', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01', // This represents the 'Price'
            'description' => 'required|string|max:255',
            'receipt_reference' => 'nullable|string|max:255',
            'inventory_mode' => 'required|in:existing,new', // 'none' is completely removed
            'quantity' => 'required|integer|min:1',
        ]);

        $supplyId = null;
        $invMode = $request->input('inventory_mode');
        $qty = (int) $request->input('quantity');

        // STRICT INVENTORY INTEGRATION
        if ($invMode === 'existing' && $request->filled('supply_id')) {
            
            $supply = Supply::findOrFail($request->input('supply_id'));
            $supply->quantity += $qty;
            $supply->save();
            
            $supplyId = $supply->id;
            ActivityLog::log('Inventory', "Purchased {$qty} {$supply->unit} of {$supply->name} via Petty Cash.");
            
        } elseif ($invMode === 'new' && $request->filled('new_item_name')) {
            
            $supply = new Supply();
            $supply->name = $request->input('new_item_name');
            $supply->category = $request->input('new_item_category') ?: 'UNCATEGORIZED';
            $supply->description = 'Purchased via Petty Cash';
            $supply->quantity = $qty;
            $supply->unit = 'pcs'; 
            $supply->reorder_level = 10;
            
            // HIDDEN BY DEFAULT: Prevents other departments from seeing/requesting this specific item
            $supply->is_visible = 0; 
            
            $supply->supplier = 'Petty Cash Purchase'; 
            $supply->date_delivered = \Carbon\Carbon::now();
            $supply->save();
            
            $supplyId = $supply->id;
            ActivityLog::log('Inventory', "Registered and purchased new item: {$supply->name} (Qty: {$supply->quantity}) via Petty Cash.");
        }

        // Save the main Ledger transaction
        PettyCashTransaction::create([
            'type' => 'Expense', // Hardcoded since they only request expenses now
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'receipt_reference' => $request->input('receipt_reference'),
            'supply_id' => $supplyId,
            'processed_by' => Auth::id(),
        ]);

        ActivityLog::log('Petty Cash', "Recorded Petty Cash expense: ₱" . number_format($request->input('amount'), 2) . " for " . $request->input('description'));

        return redirect()->back()->with('success', 'Petty cash request recorded successfully!');
    }

    public function printReceipt($id)
    {
        $transaction = PettyCashTransaction::with(['processor', 'supply'])->findOrFail($id);
        return view('superadmin.print_petty_cash', compact('transaction'));
    }
}