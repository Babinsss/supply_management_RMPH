<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supply;
use App\Models\DepartmentRequest;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SupplyController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard');
    }

    public function dashboard()
    {
        $supplies = Supply::all();
        
        // Pull and group requests manually by batch_id
        $allRequests = DepartmentRequest::with('supply')
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

        return view('dashboard', [
            'items' => $supplies, 
            'requests' => $requestsToDisplay, 
            'pending_count' => $pendingCount
        ]);
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'quantity' => 'required|integer',
            'unit' => 'required|string|max:50',
            'reorder_level' => 'required|integer',
        ]);

        Supply::create($request->all());

        return redirect()->route('dashboard')->with('success', 'Item added successfully!');
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
            return redirect()->route('dashboard')->with('danger', 'Request not found.');
        }

        if ($batchReqs->first()->status !== 'Pending') {
            return redirect()->route('dashboard')->with('warning', 'This request has already been processed.');
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
                return redirect()->route('dashboard')->with('danger', "Cannot approve! Not enough stock for {$req->supply->name}. You tried to release {$req->quantity} but only have {$req->supply->quantity}.");
            }
        }

        // Finalize state modifications safely
        foreach ($batchReqs as $req) {
            $req->supply->quantity -= $req->quantity;
            $req->supply->save();
            
            $req->status = 'Approved';
            $req->save();
        }

        return redirect()->route('dashboard')->with('success', 'Bulk request approved and stock updated!');
    }

    public function denyBatch($batch_id)
    {
        $batchReqs = DepartmentRequest::where('batch_id', $batch_id)->get();

        foreach ($batchReqs as $req) {
            $req->status = 'Denied';
            $req->save();
        }

        return redirect()->route('dashboard')->with('success', 'Bulk request denied. Stock remains unchanged.');
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

        return redirect()->route('portal')->with('success', 'Your bulk request has been successfully submitted to ICT.');
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
        return view('print_template', ['batch_requests' => $batchRequests]);
    }

    public function pendingCountApi()
    {
        $pendingCount = DepartmentRequest::where('status', 'Pending')
            ->distinct('batch_id')
            ->count('batch_id');

        return response()->json(['count' => $pendingCount]);
    }
    public function inventory()
    {
        $supplies = Supply::orderBy('name', 'asc')->get();
        return view('inventory', ['items' => $supplies]);
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
}
