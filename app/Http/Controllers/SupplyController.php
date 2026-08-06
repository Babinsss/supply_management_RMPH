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
        $this->autoCancelExpiredRequests();

        $supplies = Supply::all();
        
        // Pull and group requests manually by batch_id, including the issuer
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
        
        // Custom Paginator Logic (10 items per page)
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

        // LOG ACTION
        \App\Models\ActivityLog::log('Inventory', "Added new item: {$newItem->name} (Qty: {$newItem->quantity})");

        return redirect()->back()->with('success', 'New item added successfully!');
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

        // LOG ACTION
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

        // LOG ACTION
        \App\Models\ActivityLog::log('Inventory', "Updated details for item: {$item->name}");

        return redirect()->back()->with('success', "Item details updated successfully for {$item->name}!");
    }

    public function deleteItem($id)
    {
        $item = Supply::findOrFail($id);
        $itemName = $item->name;
        $item->delete();

        // LOG ACTION
        \App\Models\ActivityLog::log('Inventory', "Deleted item from inventory: {$itemName}");

        return redirect()->route('dashboard')->with('success', 'Item successfully deleted.');
    }

    public function processBatch(Request $request, $batch_id, $action)
    {
        // This structural placeholder delegates processing tasks safely
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

        // LOG ACTION
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

        // LOG ACTION
        if ($batchReqs->isNotEmpty()) {
            $departmentName = $batchReqs->first()->department_name;
            \App\Models\ActivityLog::log('Supply Issuance', "Denied request {$batch_id} for {$departmentName}");
        }

        return redirect()->back()->with('success', 'Bulk request denied. Stock remains unchanged.');
    }

    public function departmentPortal()
    {
        // 1. Fetch only visible supplies
        $supplies = Supply::where('is_visible', true)->orderBy('name', 'asc')->get();
        
        // 2. Fetch active departments, sorted by their group
        $departments = \App\Models\Department::where('is_active', true)
                        ->orderBy('group_name')
                        ->orderBy('name')
                        ->get();
                        
        // 3. Fetch active categories
        $categories = \App\Models\Category::where('is_active', true)
                        ->orderBy('name')
                        ->get();
        
        // 4. Pass all three to the portal view
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
            ->with('success', 'Your bulk request has been successfully submitted to ICT.')
            ->with('batch_id', $batchId);
    }

    public function printBulk($batch_id)
    {
        $batchRequests = DepartmentRequest::where('batch_id', $batch_id)->get();
        
        if ($batchRequests->isEmpty()) {
            abort(404, 'Batch not found');
        }

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
        $this->autoCancelExpiredRequests(); 

        $pendingCount = DepartmentRequest::where('status', 'Pending')
            ->distinct('batch_id')
            ->count('batch_id');

        return response()->json(['count' => $pendingCount]);
    }
    
    public function inventory() 
    {
        $supplies = Supply::orderBy('name', 'asc')->get();
        return view('inventory', compact('supplies'));
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

        $releases = DepartmentRequest::where('supply_id', $id)->where('status', 'Approved')->orderBy('created_at', 'asc')->get();

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
                    $item->id,
                    $item->name,
                    $item->category,
                    $item->description,
                    $item->quantity,
                    $item->unit,
                    $item->reorder_level
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    public function approverDashboard()
    {
        $this->autoCancelExpiredRequests();
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
            $currentItems, 
            $requestsCollection->count(), 
            $perPage, 
            $currentPage, 
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('approver.dashboard', [
            'requests' => $paginatedRequests,
            'pending_count' => $pending_count
        ]);
    }

    public function approverInventory()
    {
        $supplies = Supply::orderBy('name', 'asc')->get();
        return view('approver.inventory', compact('supplies'));
    }

    private function autoCancelExpiredRequests()
    {
        DepartmentRequest::where('status', 'Pending')
            ->where('created_at', '<', Carbon::now()->subDay(3))
            ->update(['status' => 'Denied']);
    }

    // --- ONLY ONE UNIFIED STOCKCARD FUNCTION ---
    public function stockcard(Request $request, $id) 
    {
        // 1. Get the base item the user clicked on
        $clickedItem = Supply::findOrFail($id);

        // 2. Find ALL items in the database with this exact same name
        $matchingSupplies = Supply::where('name', $clickedItem->name)->get();

        // 3. Setup Month Filter Logic
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

        // 4. Loop through each matching supply and calculate its math individually
        foreach ($matchingSupplies as $supply) {
            $releasesAsc = DepartmentRequest::where('supply_id', $supply->id)
                ->where('status', 'Approved')
                ->whereMonth('updated_at', $month_filter)
                ->whereYear('updated_at', date('Y'))
                ->orderBy('updated_at', 'asc')
                ->get();

            $futureAndCurrentIssued = DepartmentRequest::where('supply_id', $supply->id)
                ->where('status', 'Approved')
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
            
            // --- NEW: SMART SKIP ---
            // If this item has 0 stock, 0 starting balance, and 0 transactions this month... skip printing it!
            if ($supply->quantity == 0 && $balance_forwarded == 0 && $formattedReleases->isEmpty()) {
                continue;
            }
            
            // Package this specific supplier's data into our array
            $cardsData[] = [
                'item' => $supply,
                'balance_forwarded' => $balance_forwarded,
                'releases' => $formattedReleases->reverse()
            ];
        }

        // We pass the clicked item back for the Toolbar buttons (Excel/Filter)
        $item = $clickedItem;

        return view('stockcard', compact(
            'item', 'cardsData', 'month_filter', 'current_month_label', 'available_months'
        ));
    }

    public function toggleVisibility($id)
    {
        $item = Supply::findOrFail($id);
        $item->is_visible = !$item->is_visible; 
        $item->save();

        // LOG ACTION
        $status = $item->is_visible ? 'visible' : 'hidden';
        \App\Models\ActivityLog::log('Inventory', "Toggled visibility for {$item->name} to {$status}");

        return redirect()->back()->with('success', "Visibility updated for {$item->name}!");
    }
}