<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PettyCashTransaction;
use App\Models\Supply;
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

        return view('superadmin.petty_cash', compact('transactions', 'balance', 'supplies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:Replenishment,Expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'receipt_reference' => 'nullable|string|max:255',
            'supply_id' => 'nullable|exists:supplies,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        PettyCashTransaction::create([
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'receipt_reference' => $validated['receipt_reference'],
            'supply_id' => $validated['supply_id'] ?? null,
            'processed_by' => Auth::id(),
        ]);

        // SMART INTEGRATION: Automatically update stock if a supply item was purchased
        if ($validated['type'] === 'Expense' && !empty($validated['supply_id']) && !empty($validated['quantity'])) {
            $supply = Supply::findOrFail($validated['supply_id']);
            $supply->quantity += $validated['quantity'];
            $supply->save();

            ActivityLog::log('Inventory', "Purchased {$validated['quantity']} {$supply->unit} of {$supply->name} via Petty Cash.");
        }

        // Log the cash transaction
        $logDesc = $validated['type'] === 'Replenishment' 
            ? "Replenished Petty Cash by ₱" . number_format($validated['amount'], 2)
            : "Recorded Petty Cash expense: ₱" . number_format($validated['amount'], 2) . " for " . $validated['description'];
        ActivityLog::log('Petty Cash', $logDesc);

        return redirect()->back()->with('success', 'Petty cash transaction recorded successfully!');
    }
    public function printReceipt($id)
    {
        $transaction = PettyCashTransaction::with(['processor', 'supply'])->findOrFail($id);
        
        return view('superadmin.print_petty_cash', compact('transaction'));
    }
}