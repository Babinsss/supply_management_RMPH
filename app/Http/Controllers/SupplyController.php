<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supply;
use App\Models\DepartmentRequest;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SupplyController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard');
    }

   public function dashboard()
    {
        $supplies = Supply::all();
        
        $allRequests = DepartmentRequest::with(['supply', 'issuer'])
            ->orderBy('created_at', 'desc')
            ->get();

        $groupedBatches = [];
        foreach ($allRequests as $req) {
            if (!isset($groupedBatches[$req->batch_id])) {
                $groupedBatches[$req->batch_id] = [
                    'batch_id' => $req->batch_id,
                    'created_at' => $req->created_at,
                    'department_name' => $req->department_name,
                    'requested_by' => $req->requested_by,
                    'status' => $req->status,
                    'items' => []
                ];
            }
            $groupedBatches[$req->batch_id]['items'][] = $req;
        }

        $requestsToDisplay = array_values($groupedBatches);
        $pendingCount = collect($requestsToDisplay)->where('status', 'Pending')->count();
        
        $perPage = 10;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $currentItems = array_slice($requestsToDisplay, ($currentPage - 1) * $perPage, $perPage);
        
        $paginatedRequests = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems, 
            count($requestsToDisplay), 
            $perPage, 
            $currentPage, 
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('dashboard', [
            'items' => $supplies, 
            'requests' => $paginatedRequests, 
            'pending_count' => $pendingCount
        ]);
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'category'       => 'nullable|string|max:150',
            'description'    => 'nullable|string',
            'quantity'       => 'required|integer|min:0',
            'unit_price'     => 'nullable|numeric|min:0',
            'supplier'       => 'nullable|string|max:255',
            'date_delivered' => 'nullable|date',
            'expiry_date'    => 'nullable|date',
            'ris_number'     => 'nullable|string|max:255',
        ]);

        $validated['unit'] = $request->input('unit', 'pcs'); 
        $validated['reorder_level'] = $request->input('reorder_level', 10); 

        $newItem = Supply::create($validated);

        \App\Models\ActivityLog::log('Inventory', "Added new item: {$newItem->name} (Qty: {$newItem->quantity})");

        // Pass the category back to the view so it opens the correct tab
        return redirect()->back()
            ->with('success', 'New item added successfully!')
            ->with('active_category', $newItem->category);
    }

    public function updateStock(Request $request, $id)
    {
        $item = Supply::findOrFail($id);
        $adjustment = (int)$request->input('adjustment');

        $item->quantity += $adjustment;
        if ($item->quantity < 0) {
            $item->quantity = 0;
        }
        $item->save();

        \App\Models\ActivityLog::log('Inventory', "Adjusted stock for {$item->name} by {$adjustment}. New total: {$item->quantity}");

        return redirect()->route('dashboard')->with('success', "Stock updated successfully for {$item->name}!");
    }
    
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category'       => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0', 
            'ris_number' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'supplier' => 'nullable|string|max:255',
            'date_delivered' => 'nullable|date',
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        $item = Supply::findOrFail($id);
        $item->update($validated);

        \App\Models\ActivityLog::log('Inventory', "Updated details for item: {$item->name}");

        // Pass the category back to the view so it opens the correct tab
        return redirect()->back()
            ->with('success', "Item details updated successfully for {$item->name}!")
            ->with('active_category', $item->category);
    }

    public function deleteItem($id)
    {
        $item = Supply::findOrFail($id);
        $itemName = $item->name;
        $item->delete();

        \App\Models\ActivityLog::log('Inventory', "Deleted item from inventory: {$itemName}");

        return redirect()->route('dashboard')->with('success', 'Item successfully deleted.');
    }

    public function processBatch(Request $request, $batch_id, $action)
    {
    }

    public function approveBatch(Request $request, $batch_id)
    {
        $batchReqs = DepartmentRequest::where('batch_id', $batch_id)->get();

        if ($batchReqs->isEmpty()) {
            return redirect()->back()->with('danger', 'Request not found.');
        }

        if ($batchReqs->first()->status !== 'Pending') {
            return redirect()->back()->with('warning', 'This request has already been processed.');
        }

        foreach ($batchReqs as $req) {
            $adjQty = $request->input("qty_{$req->id}");
            if ($adjQty !== null) {
                $req->quantity = (int)$adjQty;
            }
        }

        foreach ($batchReqs as $req) {
            if ($req->supply->quantity < $req->quantity) {
                return redirect()->back()->with('danger', "Cannot approve! Not enough stock for {$req->supply->name}. You tried to release {$req->quantity} but only have {$req->supply->quantity}.");
            }
        }

        foreach ($batchReqs as $req) {
            $req->supply->quantity -= $req->quantity;
            $req->supply->save();
            
            $req->status = 'Approved';
            $req->issued_by = Auth::id(); 
            $req->save();
        }

        $departmentName = $batchReqs->first()->department_name;
        \App\Models\ActivityLog::log('Supply Issuance', "Approved and issued request {$batch_id} to {$departmentName}");

        return redirect()->back()->with('success', 'Bulk request approved and stock updated!');
    }

    public function denyBatch($batch_id)
    {
        $batchReqs = DepartmentRequest::where('batch_id', $batch_id)->get();

        foreach ($batchReqs as $req) {
            $req->status = 'Denied';
            $req->save();
        }

        if ($batchReqs->isNotEmpty()) {
            $departmentName = $batchReqs->first()->department_name;
            \App\Models\ActivityLog::log('Supply Issuance', "Denied request {$batch_id} for {$departmentName}");
        }

        return redirect()->back()->with('success', 'Bulk request denied. Stock remains unchanged.');
    }

    public function departmentPortal()
    {
        $supplies = Supply::where('is_visible', true)->orderBy('name', 'asc')->get();
        $departments = \App\Models\Department::where('is_active', true)->orderBy('group_name')->orderBy('name')->get();
        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get();
        
        return view('portal', compact('supplies', 'departments', 'categories'));
    }

    public function submitRequest(Request $request)
    {
        $dept = $request->input('department_name');
        $person = $request->input('requested_by');
        $purpose = $request->input('purpose');
        $cartJson = $request->input('cart_data', '[]');

        $cartData = json_decode($cartJson, true) ?? [];

        if (empty($cartData)) {
            return redirect()->route('portal')->with('danger', 'Please add items to your cart before submitting.');
        }

        $batchId = (string) Str::uuid();

        foreach ($cartData as $item) {
            DepartmentRequest::create([
                'batch_id' => $batchId,
                'department_name' => $dept,
                'requested_by' => $person,
                'supply_id' => (int)$item['id'],
                'quantity' => (int)$item['qty'],
                'purpose' => $purpose,
                'status' => 'Pending'
            ]);
        }

        return redirect()->route('portal')
            ->with('success', 'Your request has been successfully submitted to Supply Section.')
            ->with('batch_id', $batchId);
    }

    public function printBulk($batch_id)
    {
        $batchRequests = DepartmentRequest::where('batch_id', $batch_id)->get();
        if ($batchRequests->isEmpty()) abort(404, 'Batch not found');

        $sequence = DepartmentRequest::where('batch_id', '!=', $batch_id)
                        ->whereMonth('created_at', now()->month)
                        ->distinct('batch_id')
                        ->count() + 1;
        
        $controlNumber = 'RIS-' . now()->format('Y-m') . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);

        return view('print_template', [
            'batch_requests' => $batchRequests,
            'control_number' => $controlNumber
        ]);
    }

    public function pendingCountApi()
    {
        $pendingCount = DepartmentRequest::where('status', 'Pending')->distinct('batch_id')->count('batch_id');
        return response()->json(['count' => $pendingCount]);
    }
    
    public function inventory() 
    {
        $supplies = Supply::orderBy('name', 'asc')->get();
        $categories = \App\Models\Category::orderBy('name')->get(); 
        return view('inventory', compact('supplies', 'categories'));
    }

    public function printInventory(Request $request)
    {
        $query = Supply::withSum('departmentRequests', 'quantity');

        if ($request->has('category') && $request->category !== 'ALL') {
            $query->where('category', $request->category);
        }

        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('created_at', $request->month)
                  ->whereYear('created_at', $request->year);
        }

        $supplies = $query->orderBy('name', 'asc')->get();
        $reportMonth = $request->month ? Carbon::createFromFormat('m', $request->month)->format('F') : null;
        $reportYear = $request->year;
        $reportCategory = $request->category;

        return view('print_inventory', compact('supplies', 'reportMonth', 'reportYear', 'reportCategory'));
    }

    public function exportExcel(Request $request, $id)
    {
        $item = Supply::findOrFail($id);
        $monthFilter = $request->input('month', Carbon::now()->format('Y-m'));
        
        // FIX: Ignore 0 quantity issues
        $releases = DepartmentRequest::where('supply_id', $id)
            ->where('status', 'Approved')
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();
            
        $filename = "Stockcard_{$item->name}_{$monthFilter}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        $columns = ['Date', 'Reference', 'Department', 'Requested By', 'Qty Issued', 'Running Balance'];

        $callback = function () use ($releases, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $balance = 0; 
            foreach ($releases as $release) {
                $balance -= $release->quantity; 
                fputcsv($file, [
                    Carbon::parse($release->created_at)->format('m/d/Y'),
                    strtoupper(substr($release->batch_id, 0, 8)),
                    $release->department_name,
                    $release->requested_by,
                    $release->quantity,
                    $balance
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
    
    public function exportInventoryExcel()
    {
        $supplies = Supply::orderBy('name', 'asc')->get();
        $filename = "RMPH_Full_Inventory_" . date('Y-m-d') . ".csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        $columns = ['ID', 'Item Name', 'Category', 'Description', 'Quantity', 'Unit', 'Reorder Alert Level'];

        $callback = function() use($supplies, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($supplies as $item) {
                fputcsv($file, [
                    $item->id, $item->name, $item->category, $item->description, $item->quantity, $item->unit, $item->reorder_level
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
    
    public function approverDashboard()
    {
        $allRequests = DepartmentRequest::with(['supply', 'issuer'])->orderBy('created_at', 'desc')->get();
        
        $requestsCollection = $allRequests->groupBy('batch_id')->map(function ($items, $batchId) {
            return [
                'batch_id' => $batchId,
                'created_at' => $items->first()->created_at,
                'department_name' => $items->first()->department_name,
                'requested_by' => $items->first()->requested_by,
                'status' => $items->first()->status,
                'items' => $items
            ];
        })->values();

        $pending_count = $requestsCollection->where('status', 'Pending')->count();
        $perPage = 10;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $currentItems = $requestsCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();
        
        $paginatedRequests = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems, $requestsCollection->count(), $perPage, $currentPage, ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('approver.dashboard', ['requests' => $paginatedRequests, 'pending_count' => $pending_count]);
    }

    public function approverInventory()
    {
        $supplies = Supply::orderBy('name', 'asc')->get();
        return view('approver.inventory', compact('supplies'));
    }

    public function stockcard(Request $request, $id) 
    {
        $clickedItem = Supply::findOrFail($id);
        $matchingSupplies = Supply::where('name', $clickedItem->name)->get();
        $month_filter = $request->input('month', date('m'));
        $current_month_label = date('F Y', mktime(0, 0, 0, $month_filter, 1, date('Y')));
        
        $available_months = [];
        for ($i = 1; $i <= 12; $i++) {
            $available_months[] = [
                'value' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'label' => date('F', mktime(0, 0, 0, $i, 1))
            ];
        }

        $cardsData = [];

        foreach ($matchingSupplies as $supply) {
            // FIX: Added ->where('quantity', '>', 0) to hide 0-qty ghost entries
            $releasesAsc = DepartmentRequest::where('supply_id', $supply->id)
                ->where('status', 'Approved')
                ->where('quantity', '>', 0) 
                ->whereMonth('updated_at', $month_filter)
                ->whereYear('updated_at', date('Y'))
                ->orderBy('updated_at', 'asc')
                ->get();

            // FIX: Added ->where('quantity', '>', 0) here as well for flawless math
            $futureAndCurrentIssued = DepartmentRequest::where('supply_id', $supply->id)
                ->where('status', 'Approved')
                ->where('quantity', '>', 0)
                ->where('updated_at', '>=', date('Y') . '-' . $month_filter . '-01 00:00:00')
                ->sum('quantity');

            $balance_forwarded = $supply->quantity + $futureAndCurrentIssued;
            $tempBalance = $balance_forwarded;
            $formattedReleases = collect();
            
            foreach ($releasesAsc as $release) {
                $tempBalance -= $release->quantity;
                $release->running_balance = $tempBalance;
                $formattedReleases->push($release);
            }
            
            if ($supply->quantity == 0 && $balance_forwarded == 0 && $formattedReleases->isEmpty()) {
                continue;
            }
            
            $cardsData[] = [
                'item' => $supply,
                'balance_forwarded' => $balance_forwarded,
                'releases' => $formattedReleases->reverse()
            ];
        }

        $item = $clickedItem;
        return view('stockcard', compact('item', 'cardsData', 'month_filter', 'current_month_label', 'available_months'));
    }

    public function toggleVisibility($id)
    {
        $item = Supply::findOrFail($id);
        $item->is_visible = !$item->is_visible; 
        $item->save();

        $status = $item->is_visible ? 'visible' : 'hidden';
        \App\Models\ActivityLog::log('Inventory', "Toggled visibility for {$item->name} to {$status}");

        return redirect()->back()->with('success', "Visibility updated for {$item->name}!");
    }

    // --- NEW BIN CARD REPORT ---
    public function bincard($id)
    {
        $item = Supply::findOrFail($id);
        
        // FIX: Ignore 0 quantity issues
        $releases = \App\Models\DepartmentRequest::where('supply_id', $item->id)
            ->where('status', 'Approved')
            ->where('quantity', '>', 0)
            ->orderBy('updated_at', 'asc')
            ->get();

        $totalIssued = $releases->sum('quantity');
        $startingBalance = $item->quantity + $totalIssued;

        $transactions = collect();
        $transactions->push([
            'date' => $item->date_delivered ? $item->date_delivered : $item->created_at,
            'department' => $item->supplier ?: 'Initial Inventory',
            'delivery_qty' => $startingBalance,
            'qty_issued' => '',
            'balance' => $startingBalance
        ]);

        $currentBalance = $startingBalance;
        foreach ($releases as $release) {
            $currentBalance -= $release->quantity;
            $transactions->push([
                'date' => $release->updated_at,
                'department' => $release->department_name,
                'delivery_qty' => '',
                'qty_issued' => $release->quantity,
                'balance' => $currentBalance
            ]);
        }

        return view('bincard', compact('item', 'transactions'));
    }

    // --- NEW REPORT ON ACTUAL PHYSICAL COUNT (RAPC) ---
    public function rapc($id)
    {
        $baseItem = Supply::findOrFail($id);

        if ($baseItem->supplier) {
            $items = Supply::where('supplier', $baseItem->supplier)->orderBy('name', 'asc')->get();
        } else {
            $items = collect([$baseItem]);
        }

        $risNumbers = $items->pluck('ris_number')->filter()->map(function($ris) { return trim($ris); })->unique()->implode(', ');
        $risDates = $items->pluck('date_delivered')->filter()->map(function($d) { return \Carbon\Carbon::parse($d)->format('m/d/Y'); })->unique()->implode(', ');

        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');

        foreach ($items as $item) {
            // FIX: Ignore 0 quantity issues
            $releasesThisYear = \App\Models\DepartmentRequest::where('supply_id', $item->id)
                ->where('status', 'Approved')
                ->where('quantity', '>', 0)
                ->whereYear('updated_at', $currentYear)
                ->get();

            $monthlyBalances = [];
            for ($m = 1; $m <= 12; $m++) {
                if ($currentYear == 2026 && $m < 8) {
                    $monthlyBalances[$m] = ''; 
                } 
                elseif ($m > $currentMonth) {
                    $monthlyBalances[$m] = ''; 
                } 
                else {
                    $releasesAfterM = $releasesThisYear->filter(function($req) use ($m) {
                        return \Carbon\Carbon::parse($req->updated_at)->month > $m;
                    })->sum('quantity');

                    $monthlyBalances[$m] = $item->quantity + $releasesAfterM;
                }
            }
            $item->monthly_balances = $monthlyBalances;
        }

        return view('rapc', compact('baseItem', 'items', 'risNumbers', 'risDates'));
    }
    public function printPettyCashRequest(Request $request)
    {
        $data = $request->validate([
            'department_name' => 'required|string',
            'requested_by' => 'required|string',
            // Update validations to expect arrays
            'item_name' => 'required|array|min:1',
            'item_name.*' => 'required|string',
            'estimated_price' => 'required|array|min:1',
            'estimated_price.*' => 'required|numeric',
            'quantity' => 'required|array|min:1',
            'quantity.*' => 'required|integer|min:1',
            'purpose' => 'required|string',
        ]);

        return view('print_petty_cash_ris', compact('data'));
    }
    public function liveInventory()
    {
        // Fetches only the numbers we need to keep the background check lightning fast
        $supplies = \App\Models\Supply::select('id', 'quantity', 'reorder_level')->get();
        return response()->json($supplies);
    }
    // --- SUPERADMIN STOCK ADJUSTMENT ---
    public function adjustStock(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:add,subtract',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255'
        ]);

        $item = Supply::findOrFail($id);
        $qty = (int) $request->input('quantity');
        $action = $request->input('action');
        $reason = strtoupper($request->input('reason'));

        if ($action === 'subtract') {
            if ($item->quantity < $qty) {
                return redirect()->back()->with('danger', 'Cannot deduct more than the current stock available!');
            }
            
            $item->quantity -= $qty;
            
            // CREATES A PAPER TRAIL FOR THE STOCK CARD
            \App\Models\DepartmentRequest::create([
                'batch_id' => 'ADJ-' . strtoupper(\Illuminate\Support\Str::random(5)),
                'department_name' => 'SYSTEM ADJUSTMENT',
                'requested_by' => $reason,
                'supply_id' => $item->id,
                'quantity' => $qty,
                'purpose' => 'Superadmin correction',
                'status' => 'Approved',
                'issued_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);
            
            \App\Models\ActivityLog::log('Inventory', "Adjusted stock DOWN for {$item->name} by {$qty}. Reason: {$reason}");
            
        } else {
            // Addition: Math works inherently by bumping the Balance Forwarded
            $item->quantity += $qty;
            \App\Models\ActivityLog::log('Inventory', "Adjusted stock UP for {$item->name} by {$qty}. Reason: {$reason}");
        }

        $item->save();

        return redirect()->back()
            ->with('success', "Stock successfully adjusted for {$item->name}!")
            ->with('active_category', $item->category);
    }
}