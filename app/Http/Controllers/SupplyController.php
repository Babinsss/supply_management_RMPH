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
        // 1. Validate only the fields that actually exist in your modal
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'quantity'       => 'required|integer|min:0',
            'unit_price'     => 'nullable|numeric|min:0',
            'supplier'       => 'nullable|string|max:255',
            'date_delivered' => 'nullable|date',
            'expiry_date'    => 'nullable|date',
            'ris_number'     => 'nullable|string|max:255',
        ]);

        // 2. Set default values for the required database columns not in the modal
        $validated['unit'] = $request->input('unit', 'pcs'); 
        $validated['reorder_level'] = $request->input('reorder_level', 10); 

        // 3. Create the item
        Supply::create($validated);

        // 4. Redirect safely back to the Inventory page (not the dashboard!)
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

        return redirect()->route('dashboard')->with('success', "Stock updated successfully for {$item->name}!");
    }
    
    public function update(Request $request, $id)
    {
        // 1. Validate all the fields coming from the Edit Modal
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0', // This replaces the old quantity completely
            'ris_number' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'supplier' => 'nullable|string|max:255',
            'date_delivered' => 'nullable|date',
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        // 2. Find the item and overwrite the data
        $item = Supply::findOrFail($id);
        $item->update($validated);

        // 3. Redirect back to the inventory page with a success message
        return redirect()->back()->with('success', "Item details updated successfully for {$item->name}!");
    }

    public function deleteItem($id)
    {
        $item = Supply::findOrFail($id);
        $item->delete();

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

        // Apply updated quantities from modal forms if submitted
        foreach ($batchReqs as $req) {
            $adjQty = $request->input("qty_{$req->id}");
            if ($adjQty !== null) {
                $req->quantity = (int)$adjQty;
            }
        }

        // Validate stock allocation counts
        foreach ($batchReqs as $req) {
            if ($req->supply->quantity < $req->quantity) {
                return redirect()->back()->with('danger', "Cannot approve! Not enough stock for {$req->supply->name}. You tried to release {$req->quantity} but only have {$req->supply->quantity}.");
            }
        }

        // Finalize state modifications safely
        foreach ($batchReqs as $req) {
            $req->supply->quantity -= $req->quantity;
            $req->supply->save();
            
            $req->status = 'Approved';
            $req->issued_by = Auth::id(); // Record who issued it
            $req->save();
        }

        return redirect()->back()->with('success', 'Bulk request approved and stock updated!');
    }

    public function denyBatch($batch_id)
    {
        $batchReqs = DepartmentRequest::where('batch_id', $batch_id)->get();

        foreach ($batchReqs as $req) {
            $req->status = 'Denied';
            $req->save();
        }

        return redirect()->back()->with('success', 'Bulk request denied. Stock remains unchanged.');
    }

    public function departmentPortal()
    {
        // Fetch all supplies and order them alphabetically
        $supplies = Supply::orderBy('name', 'asc')->get();
        
        return view('portal', ['supplies' => $supplies]);
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

        // We added the batch_id here so the portal knows which button to show!
        return redirect()->route('portal')
            ->with('success', 'Your bulk request has been successfully submitted to ICT.')
            ->with('batch_id', $batchId);
    }

    public function stockcard(Request $request, $item_id)
    {
        $item = Supply::findOrFail($item_id);
        
        // Match system timezone parameters cleanly 
        $monthFilter = $request->input('month', Carbon::now()->format('Y-m'));

        $allReleases = DepartmentRequest::where('supply_id', $item_id)
            ->where('status', 'Approved')
            ->orderBy('created_at', 'desc')
            ->get();

        // Trace retroactive stock balances running backwards
        $currentBal = $item->quantity;
        foreach ($allReleases as $release) {
            $release->running_balance = $currentBal;
            $currentBal += $release->quantity;
        }

        // Apply filters
        $monthlyReleases = $allReleases->filter(function ($r) use ($monthFilter) {
            return $r->created_at->format('Y-m') === $monthFilter;
        });

        $olderReleases = $allReleases->filter(function ($r) use ($monthFilter) {
            return $r->created_at->format('Y-m') < $monthFilter;
        });

        $balanceForwarded = !$olderReleases->isEmpty() ? $olderReleases->first()->running_balance : $currentBal;

        // Process distinct months collection lists
        $availableMonths = $allReleases->map(function ($r) {
            return $r->created_at->format('Y-m');
        })->unique()->toArray();

        if (!in_array($monthFilter, $availableMonths)) {
            array_unshift($availableMonths, $monthFilter);
        }
        rsort($availableMonths);

        $availableMonthsFormatted = [];
        foreach ($availableMonths as $m) {
            $availableMonthsFormatted[] = [
                'value' => $m,
                'label' => strtoupper(Carbon::parse($m)->format('F Y'))
            ];
        }

        $currentMonthLabel = strtoupper(Carbon::parse($monthFilter)->format('F Y'));

        return view('stockcard', [
            'item' => $item,
            'releases' => $monthlyReleases,
            'month_filter' => $monthFilter,
            'available_months' => $availableMonthsFormatted,
            'current_month_label' => $currentMonthLabel,
            'balance_forwarded' => $balanceForwarded
        ]);
    }

    public function printBulk($batch_id)
    {
        $batchRequests = DepartmentRequest::where('batch_id', $batch_id)->get();
        
        if ($batchRequests->isEmpty()) {
            abort(404, 'Batch not found');
        }

        // Generate formatted control number: RIS-YYYY-MM-###
        // We count batches created in the same month to get the sequence
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
        $this->autoCancelExpiredRequests(); // <-- ADD THIS LINE

        $pendingCount = DepartmentRequest::where('status', 'Pending')
            ->distinct('batch_id')
            ->count('batch_id');

        return response()->json(['count' => $pendingCount]);
    }
    
    public function inventory() 
    {
        // Fetch all supplies from the database, ordered alphabetically by name
        $supplies = \App\Models\Supply::orderBy('name', 'asc')->get();

        // Pass the $supplies variable to the inventory view
        return view('inventory', compact('supplies'));
    }

    // NEW FUNCTION: Full Inventory Print Layout
    public function printInventory()
    {
        // Fetch all items, ordered alphabetically by name
        $supplies = Supply::orderBy('name', 'asc')->get();
        
        return view('print_inventory', compact('supplies'));
    }

    public function exportExcel(Request $request, $id)
    {
        $item = Supply::findOrFail($id);
        $monthFilter = $request->input('month', \Carbon\Carbon::now()->format('Y-m'));

        // Grab the same releases data you use for your stockcard
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

            $balance = 0; // Or grab your starting balance logic here

            foreach ($releases as $release) {
                $balance -= $release->quantity; // Example balance math

                fputcsv($file, [
                    \Carbon\Carbon::parse($release->created_at)->format('m/d/Y'),
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
        // Grab everything in the database
        $supplies = Supply::orderBy('name', 'asc')->get();
        
        // Name the file dynamically based on today's date
        $filename = "RMPH_Full_Inventory_" . date('Y-m-d') . ".csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Define the top row headers for Excel
        $columns = ['ID', 'Item Name', 'Category', 'Description', 'Quantity', 'Unit', 'Reorder Alert Level'];

        $callback = function() use($supplies, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Loop through all items and fill the rows
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
    
    /**
     * QMO Approver Dashboard
     * Fetches grouped requests exactly like the main dashboard.
     */
    public function approverDashboard()
    {
        $this->autoCancelExpiredRequests();
        // Fetch all requests and group them by batch_id, including the issuer
        $allRequests = \App\Models\DepartmentRequest::with(['supply', 'issuer'])->orderBy('created_at', 'desc')->get();
        
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

        // Calculate pending batches for the stat counter
        $pending_count = $requestsCollection->where('status', 'Pending')->count();

        // NEW: Custom Paginator Logic (10 items per page)
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

        // Send data to the new Approver Dashboard view
        return view('approver.dashboard', [
            'requests' => $paginatedRequests,
            'pending_count' => $pending_count
        ]);
    }

    /**
     * QMO Approver Inventory
     * Fetches the inventory list for read-only viewing.
     */
    public function approverInventory()
    {
        // Fetch all supplies, ordered alphabetically
        $supplies = \App\Models\Supply::orderBy('name', 'asc')->get();
        
        // Send data to the new Approver Inventory view
        return view('approver.inventory', compact('supplies'));
    }

    /**
     * Auto Cancel Expired Requests
     * Cancels any 'Pending' requests older than 24 hours.
     */
    private function autoCancelExpiredRequests()
    {
        // Find any 'Pending' requests older than 24 hours and set them to 'Denied' (Cancelled)
        DepartmentRequest::where('status', 'Pending')
            ->where('created_at', '<', Carbon::now()->subDay(2))
            ->update(['status' => 'Denied']);
    }
}