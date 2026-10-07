<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supply;
use App\Models\DepartmentRequest;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SupplyController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard');
    }

   public function dashboard()
    {
        $role = Auth::user()->role;
        
        $supplyQuery = Supply::query();

        if ($role === 'forms_admin') {
            $supplyQuery->where('category', 'Forms');
        } elseif ($role === 'admin') {
            $supplyQuery->where(function($q) {
                $q->where('category', '!=', 'Forms')->orWhereNull('category');
            });
        }

        $supplies = $supplyQuery->get()->groupBy('name')->map(function ($group) {
            $first = $group->first();
            $first->quantity = $group->sum('quantity');
            $first->ris_number = $group->pluck('ris_number')->map(function($v) { return trim($v); })->filter()->unique()->implode(', ');
            return $first;
        })->values();

        $criticalSupplies = $supplies->filter(function($item) {
            return $item->quantity <= $item->reorder_level;
        })->sortBy('quantity')->values();
        
        $lowStockCount = $criticalSupplies->count();
        
        $chartDates = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
            $chartDates[] = \Carbon\Carbon::now()->subDays($i)->format('M d');

            $reqsQuery = DepartmentRequest::whereDate('created_at', $date)
                ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT']);

            if ($role === 'forms_admin') {
                $reqsQuery->whereHas('supply', function($q) {
                    $q->where('category', 'Forms');
                });
            } elseif ($role === 'admin') {
                $reqsQuery->whereDoesntHave('supply', function($q) {
                    $q->where('category', 'Forms');
                });
            }
            $chartData[] = $reqsQuery->distinct('batch_id')->count('batch_id');
        }

        $allRequestsQuery = DepartmentRequest::with(['supply', 'issuer'])
            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT'])
            ->where('batch_id', 'not like', 'ADJ-%');
        
        if ($role === 'forms_admin') {
            $allRequestsQuery->whereHas('supply', function($q) {
                $q->where('category', 'Forms');
            });
        } elseif ($role === 'admin') {
            $allRequestsQuery->whereDoesntHave('supply', function($q) {
                $q->where('category', 'Forms');
            });
        }

        $allRequests = $allRequestsQuery
            ->orderByRaw("FIELD(status, 'Pending', 'Approved', 'Denied')")
            ->orderByRaw("CASE WHEN status = 'Pending' THEN created_at END ASC")
            ->orderByRaw("CASE WHEN status != 'Pending' THEN created_at END DESC")
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
            'pending_count' => $pendingCount,
            'low_stock_count' => $lowStockCount,
            'chartDates' => $chartDates,
            'chartData' => $chartData,
            'criticalSupplies' => $criticalSupplies
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

        return redirect('/inventory')
            ->with('success', 'New item added successfully!')
            ->with('active_category', $newItem->category);
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
            'unit' => 'nullable|string|max:50',
        ]);

        $item = Supply::findOrFail($id);
        $item->fill($validated);
        $changes = $item->getDirty();
        $item->save();

        if (!empty($changes)) {
            $changeDetails = [];
            foreach ($changes as $field => $newValue) {
                if ($field === 'updated_at') continue; 
                
                $oldValue = $item->getOriginal($field);
                $oldValueStr = $oldValue === null || $oldValue === '' ? 'Empty' : $oldValue;
                $newValueStr = $newValue === null || $newValue === '' ? 'Empty' : $newValue;
                $cleanField = ucwords(str_replace('_', ' ', $field));
                $changeDetails[] = "{$cleanField} ('{$oldValueStr}' ➔ '{$newValueStr}')";
            }
            
            $changeString = implode(', ', $changeDetails);
            $logMessage = "Updated {$item->name} | Changes: " . $changeString;
        } else {
            $logMessage = "Updated details for item: {$item->name} (No actual values changed)";
        }

        \App\Models\ActivityLog::log('Inventory', $logMessage);

        return redirect('/inventory')
            ->with('success', "Item details updated successfully for {$item->name}!")
            ->with('active_category', $item->category);
    }

    public function deleteItem($id)
    {
        $item = Supply::findOrFail($id);
        $itemName = $item->name;
        $item->delete();

        \App\Models\ActivityLog::log('Inventory', "Deleted item from inventory: {$itemName}");

        return redirect('/inventory')->with('success', 'Item successfully archived.');
    }

    public function restoreItem($id)
    {
        $item = Supply::withTrashed()->findOrFail($id);
        $itemName = $item->name;
        
        $item->restore();

        \App\Models\ActivityLog::log('Inventory', "Restored soft-deleted item: {$itemName}");

        return redirect()->back()->with('success', "Item {$itemName} has been successfully restored.");
    }

    public function processBatch(Request $request, $batch_id, $action)
    {
    }

    public function approveBatch(Request $request, $batch_id)
    {
        $role = Auth::user()->role;
        
        $query = DepartmentRequest::where('batch_id', $batch_id);

        if ($role === 'forms_admin') {
            $query->whereHas('supply', function($q) {
                $q->where('category', 'Forms');
            });
        } elseif ($role === 'admin') {
            $query->where(function($q) {
                $q->whereHas('supply', function($subQ) {
                    $subQ->where('category', '!=', 'Forms')->orWhereNull('category');
                })->orWhereDoesntHave('supply');
            });
        }

        $batchReqs = $query->get();

        if ($batchReqs->isEmpty()) {
            return redirect()->back()->with('danger', 'No authorized items found for approval in this request.');
        }

        if ($batchReqs->first()->status !== 'Pending') {
            return redirect()->back()->with('warning', 'These specific items have already been processed.');
        }

        $isEmergency = $batchReqs->first()->is_emergency;
        $batchTime = $batchReqs->first()->created_at;
        
        if (!$isEmergency) {
            if ($role === 'admin') {
                $olderPendingExists = DepartmentRequest::where('status', 'Pending')
                    ->where('created_at', '<', $batchTime)
                    ->where(function($q) {
                        $q->whereHas('supply', function($subQ) {
                            $subQ->where('category', '!=', 'Forms')->orWhereNull('category');
                        })->orWhereDoesntHave('supply'); 
                    })
                    ->exists();

                if ($olderPendingExists) {
                    return redirect()->back()->with('danger', "Cannot approve! You must process your oldest pending department request first.");
                }
            }
        }

        foreach ($batchReqs as $req) {
            $adjQty = $request->input("qty_{$req->id}");
            if ($adjQty !== null) {
                $req->quantity = (int)$adjQty;
            }
        }

        $supplySums = [];
        foreach ($batchReqs as $req) {
            $name = $req->supply->name;
            if (!isset($supplySums[$name])) {
                $supplySums[$name] = 0;
            }
            $supplySums[$name] += $req->quantity;
        }

        foreach ($supplySums as $name => $totalRequested) {
            $combinedStock = Supply::where('name', $name)->sum('quantity');
            if ($combinedStock < $totalRequested) {
                return redirect()->back()->with('danger', "Cannot approve! Total requested for {$name} ({$totalRequested}) exceeds available combined stock ({$combinedStock}).");
            }
        }
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($batchReqs) {
            foreach ($batchReqs as $req) {
                $itemName = $req->supply->name;
                $remainingToDeduct = $req->quantity;
                
                $matchingSupplies = Supply::where('name', $itemName)
                    ->where('quantity', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                $firstMatch = true;
                
                foreach ($matchingSupplies as $supply) {
                    if ($remainingToDeduct <= 0) break;
                    
                    $deductAmount = min($supply->quantity, $remainingToDeduct);
                    $supply->quantity -= $deductAmount;
                    $supply->save();
                    
                    if ($firstMatch) {
                        $req->supply_id = $supply->id;
                        $req->quantity = $deductAmount;
                        $req->status = 'Approved';
                        $req->issued_by = Auth::id();
                        $req->save();
                        $firstMatch = false;
                    } else {
                        $newReq = $req->replicate();
                        $newReq->supply_id = $supply->id;
                        $newReq->quantity = $deductAmount;
                        $newReq->status = 'Approved';
                        $newReq->issued_by = Auth::id();
                        $newReq->save();
                    }
                    
                    $remainingToDeduct -= $deductAmount;
                }
            }
        });

        $departmentName = $batchReqs->first()->department_name;
        \App\Models\ActivityLog::log('Supply Issuance', "Approved and issued request {$batch_id} to {$departmentName}");

        return redirect()->back()->with('success', 'Your department items in this request were approved!');
    }

    public function denyBatch($batch_id)
    {
        $role = Auth::user()->role;
        
        $query = DepartmentRequest::where('batch_id', $batch_id);

        if ($role === 'forms_admin') {
            $query->whereHas('supply', function($q) {
                $q->where('category', 'Forms');
            });
        } elseif ($role === 'admin') {
            $query->where(function($q) {
                $q->whereHas('supply', function($subQ) {
                    $subQ->where('category', '!=', 'Forms')->orWhereNull('category');
                })->orWhereDoesntHave('supply');
            });
        }

        $batchReqs = $query->get();

        foreach ($batchReqs as $req) {
            $req->status = 'Denied';
            $req->save();
        }

        if ($batchReqs->isNotEmpty()) {
            $departmentName = $batchReqs->first()->department_name;
            \App\Models\ActivityLog::log('Supply Issuance', "Denied request {$batch_id} for {$departmentName}");
        }

        return redirect()->back()->with('success', 'Your specific items in this request were denied. Stock remains unchanged.');
    }

    public function departmentPortal()
    {
        $supplies = Supply::where('is_visible', true)->orderBy('name', 'asc')->get()
            ->groupBy('name')->map(function ($group) {
                $first = $group->first();
                $first->quantity = $group->sum('quantity');
                return $first;
            })->values();

        $departments = \App\Models\Department::where('is_active', true)->orderBy('group_name')->orderBy('name')->get();
        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get();
        
        $now = \Carbon\Carbon::now()->timezone('Asia/Manila');
        $dayOfWeek = $now->dayOfWeekIso; 
        $currentHour = $now->hour;       

        if (in_array($dayOfWeek, [6, 7])) {
            $portalMode = 'CLOSED_WEEKEND';
        } elseif ($currentHour < 8 || $currentHour >= 17) {
            $portalMode = 'CLOSED_OFF_HOURS';
        } elseif (in_array($dayOfWeek, [1, 3])) {
            $portalMode = 'NORMAL';
        } else {
            $portalMode = 'EMERGENCY_ONLY';
        }
        
        return view('portal', compact('supplies', 'departments', 'categories', 'portalMode'));
    }

    public function submitRequest(Request $request)
    {
        $dept = $request->input('department_name');
        $person = $request->input('requested_by');
        $purpose = $request->input('purpose');
        $isEmergency = $request->input('is_emergency') == '1';
        $cartJson = $request->input('cart_data', '[]');

        $cartData = json_decode($cartJson, true) ?? [];

        if (empty($cartData)) {
            return redirect()->route('portal')->with('danger', 'Please add items to your cart before submitting.');
        }

        $now = \Carbon\Carbon::now()->timezone('Asia/Manila');
        $dayOfWeek = $now->dayOfWeekIso;
        $currentHour = $now->hour;
        
        if (in_array($dayOfWeek, [6, 7])) {
            return redirect()->route('portal')->with('danger', 'System is closed. No requests can be processed during the weekend.');
        }
        if ($currentHour < 8 || $currentHour >= 17) {
            return redirect()->route('portal')->with('danger', 'System is closed. Standard requesting is only available between 8:00 AM and 5:00 PM.');
        }
        if (in_array($dayOfWeek, [2, 4, 5]) && !$isEmergency) {
            return redirect()->route('portal')->with('danger', 'Today is restricted to Emergency Requests only. Please ensure the emergency justification is provided.');
        }

        if ($isEmergency) {
            $purpose = '[EMERGENCY] ' . $purpose;
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
                'is_emergency' => $isEmergency,
                'status' => 'Pending'
            ]);
        }

        $msg = $isEmergency 
            ? 'EMERGENCY OVERRIDE LOGGED: Your urgent request has been instantly flagged for the Supply Section.' 
            : 'Your standard request has been successfully submitted to the Supply Section.';

        return redirect()->route('portal')
            ->with('success', $msg)
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
        $role = Auth::user()->role;
        $reqsQuery = DepartmentRequest::where('status', 'Pending');
        
        if ($role === 'forms_admin') {
            $reqsQuery->whereHas('supply', function($q) {
                $q->where('category', 'Forms');
            });
        } elseif ($role === 'admin') {
            $reqsQuery->where(function($q) {
                $q->whereHas('supply', function($subQ) {
                    $subQ->where('category', '!=', 'Forms')->orWhereNull('category');
                })->orWhereDoesntHave('supply');
            });
        }
        
        $pendingCount = $reqsQuery->distinct('batch_id')->count('batch_id');
        return response()->json(['count' => $pendingCount]);
    }
    
    public function inventory() 
    {
        $role = Auth::user()->role;
        $query = Supply::orderBy('name', 'asc');
        
        if ($role === 'forms_admin') {
            $query->where('category', 'Forms');
        } elseif ($role === 'admin') {
            $query->where(function($q) {
                $q->where('category', '!=', 'Forms')->orWhereNull('category');
            });
        }

        $supplies = $query->get()->groupBy('name')->map(function ($group) {
            $first = $group->first();
            $first->quantity = $group->sum('quantity');
            $first->ris_number = $group->pluck('ris_number')->map(function($v) { return trim($v); })->filter()->unique()->implode(', ');
            return $first;
        })->values();

        $categories = \App\Models\Category::orderBy('name')->get(); 
        
        return view('inventory', compact('supplies', 'categories'));
    }

    public function printInventory(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $role = Auth::user()->role;

        $query = Supply::query();

        $query->withSum(['departmentRequests' => function ($q) use ($month, $year) {
            $q->where('status', 'Approved')
              ->where('quantity', '>', 0)
              ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT']);
              
            if ($month && $year) {
                $q->whereMonth('created_at', $month)
                  ->whereYear('created_at', $year);
            }
        }], 'quantity');

        $query->whereHas('departmentRequests', function ($q) use ($month, $year) {
            $q->where('status', 'Approved')
              ->where('quantity', '>', 0)
              ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT']);
              
            if ($month && $year) {
                $q->whereMonth('created_at', $month)
                  ->whereYear('created_at', $year);
            }
        });

        if ($role === 'forms_admin') {
            $query->where('category', 'Forms');
        } elseif ($request->has('category') && $request->category !== 'ALL') {
            $query->where('category', $request->category);
        } else {
            $query->where(function($q) {
                $q->where('category', '!=', 'Forms')->orWhereNull('category');
            });
        }

        $supplies = $query->orderBy('name', 'asc')->get();

        if ($month && $year) {
            $endOfMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
            
            foreach ($supplies as $supply) {
                $futureTransactions = \App\Models\DepartmentRequest::where('supply_id', $supply->id)
                    ->where('status', 'Approved')
                    ->where('quantity', '>', 0) 
                    ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT'])
                    ->where('created_at', '>', $endOfMonth)
                    ->sum('quantity');

                $supply->quantity = $supply->quantity + $futureTransactions;
            }
        }
        
        // ==========================================
        // VIRTUAL GROUPING: Print Inventory consolidation
        // ==========================================
        $supplies = $supplies->groupBy('name')->map(function ($group) {
            $first = $group->first();
            $first->quantity = $group->sum('quantity');
            $first->department_requests_sum_quantity = $group->sum('department_requests_sum_quantity');
            
            // Calculate Beginning Balance
            $first->beginning_balance = $first->quantity + (int)$first->department_requests_sum_quantity;
            
            $first->ris_number = $group->pluck('ris_number')->map(function($v) { return trim($v); })->filter()->unique()->implode(', ');
            return $first;
        })->values();

        $reportMonth = $month ? \Carbon\Carbon::createFromFormat('m', $month)->format('F') : null;
        $reportYear = $year;
        $reportCategory = $request->category;

        return view('print_inventory', compact('supplies', 'reportMonth', 'reportYear', 'reportCategory'));
    }

    public function exportExcel(Request $request, $id)
    {
        $item = Supply::findOrFail($id);
        $monthFilter = $request->input('month', Carbon::now()->format('Y-m'));
        
        $releases = DepartmentRequest::where('supply_id', $id)
            ->where('status', 'Approved')
            ->where('quantity', '>', 0) 
            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT'])
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

        $callback = function () use ($releases, $columns, $item) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            $totalIssued = $releases->sum('quantity');
            $balance = $item->quantity + $totalIssued;
            
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
        $supplies = Supply::orderBy('name', 'asc')->get()
            ->groupBy('name')->map(function ($group) {
                $first = $group->first();
                $first->quantity = $group->sum('quantity');
                $first->ris_number = $group->pluck('ris_number')->map(function($v) { return trim($v); })->filter()->unique()->implode(', ');
                return $first;
            })->values();
            
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
        $groupedAll = Supply::all()->groupBy('name')->map(function($g) {
            $first = $g->first();
            $first->quantity = $g->sum('quantity');
            return $first;
        });
        $lowStockCount = $groupedAll->filter(function($i) { return $i->quantity <= $i->reorder_level; })->count();

        $fetchTopItems = function ($startDate) {
            $records = DepartmentRequest::where('created_at', '>=', $startDate)
                ->where('status', 'Approved')
                ->where('quantity', '>', 0)
                ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT'])
                ->selectRaw('supply_id, department_name, SUM(quantity) as total_qty')
                ->groupBy('supply_id', 'department_name')
                ->with('supply')
                ->get();

            $grouped = $records->groupBy('department_name')->map(function ($deptRecords) {
                return $deptRecords->sortByDesc('total_qty')->take(6)->values()->map(function ($row) {
                    return [
                        'name' => Str::limit($row->supply?->name ?? 'Deleted Item', 28),
                        'qty'  => (int) $row->total_qty,
                    ];
                });
            });

            $allAggregate = $records->groupBy('supply_id')->map(function ($itemRecords) {
                return [
                    'name' => Str::limit($itemRecords->first()->supply?->name ?? 'Deleted Item', 28),
                    'qty'  => $itemRecords->sum('total_qty')
                ];
            })->sortByDesc('qty')->take(6)->values();

            $grouped->put('All Departments', $allAggregate);
            return $grouped;
        };

        $chartTopItems = [
            'week'  => $fetchTopItems(now()->subDays(7)),
            'month' => $fetchTopItems(now()->subDays(30)),
            'year'  => $fetchTopItems(now()->subDays(365)),
        ];

        $activeDepartments = $chartTopItems['year']->keys()->reject(function ($key) {
            return $key === 'All Departments';
        })->sort()->values();

        $allRequests = DepartmentRequest::with(['supply', 'issuer'])
            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT'])
            ->where('batch_id', 'not like', 'ADJ-%')
            ->orderByRaw("FIELD(status, 'Pending', 'Approved', 'Denied')")
            ->orderByRaw("CASE WHEN status = 'Pending' THEN created_at END ASC")
            ->orderByRaw("CASE WHEN status != 'Pending' THEN created_at END DESC")
            ->get();
        
        $requestsCollection = collect($allRequests)->groupBy('batch_id')->map(function ($items, $batchId) {
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

        return view('approver.dashboard', [
            'requests'          => $paginatedRequests, 
            'pending_count'     => $pending_count,
            'low_stock_count'   => $lowStockCount,
            'topItemsData'      => $chartTopItems, 
            'activeDepartments' => $activeDepartments 
        ]);
    }
    
    public function approverInventory()
    {
        $supplies = Supply::orderBy('name', 'asc')->get()
            ->groupBy('name')->map(function ($group) {
                $first = $group->first();
                $first->quantity = $group->sum('quantity');
                $first->ris_number = $group->pluck('ris_number')->map(function($v) { return trim($v); })->filter()->unique()->implode(', ');
                return $first;
            })->values();
            
        return view('approver.inventory', compact('supplies'));
    }

    public function stockcard(Request $request, $id) 
    {
        $clickedItem = Supply::findOrFail($id);
        $matchingSupplies = Supply::where('name', $clickedItem->name)->get();
        
        $month_filter = $request->input('month', date('m'));
        $year_filter = $request->input('year', date('Y')); 
        
        $current_month_label = date('F Y', mktime(0, 0, 0, $month_filter, 1, $year_filter));
        
        $available_months = [];
        for ($i = 1; $i <= 12; $i++) {
            $available_months[] = [
                'value' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'label' => date('F', mktime(0, 0, 0, $i, 1))
            ];
        }

        $cardsData = [];

        foreach ($matchingSupplies as $supply) {
            $releasesAsc = DepartmentRequest::where('supply_id', $supply->id)
                ->where('status', 'Approved')
                ->where('quantity', '>', 0) 
                ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT'])
                ->whereMonth('created_at', $month_filter)
                ->whereYear('created_at', $year_filter)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc') 
                ->get();

            $futureAndCurrentIssued = DepartmentRequest::where('supply_id', $supply->id)
                ->where('status', 'Approved')
                ->where('quantity', '>', 0)
                ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT'])
                ->where('created_at', '>=', $year_filter . '-' . $month_filter . '-01 00:00:00')
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
                'releases' => $formattedReleases 
            ];
        }

        $item = $clickedItem;
        return view('stockcard', compact('item', 'cardsData', 'month_filter', 'current_month_label', 'available_months'));
    }

    public function toggleVisibility($id)
    {
        $supply = Supply::findOrFail($id);
        
        $supply->is_visible = !$supply->is_visible;
        $supply->save();

        $status = $supply->is_visible ? 'visible' : 'hidden';

        \App\Models\ActivityLog::log('Inventory', "Toggled visibility for {$supply->name} to {$status}");

        return redirect()->back()->with('success', "Item {$supply->name} is now {$status} in the portal.");
    }
    
    public function bincard($id)
    {
        $item = Supply::findOrFail($id);
        
        $releases = \App\Models\DepartmentRequest::where('supply_id', $item->id)
            ->where('status', 'Approved')
            ->where('quantity', '>', 0) 
            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT'])
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
            $releasesThisYear = \App\Models\DepartmentRequest::where('supply_id', $item->id)
                ->where('status', 'Approved')
                ->where('quantity', '>', 0) 
                ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT'])
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
            'item_name' => 'required|array|min:1|max:10', 
            'item_name.*' => 'required|string',
            'quantity' => 'required|array|min:1|max:10',  
            'quantity.*' => 'required|integer|min:1',
            'purpose' => 'required|string',
        ]);

        return view('print_petty_cash_ris', compact('data'));
    }

    public function liveInventory()
    {
        $supplies = \App\Models\Supply::select('id', 'name', 'quantity', 'reorder_level')->get()
            ->groupBy('name')->map(function($g) {
                $first = $g->first();
                $first->quantity = $g->sum('quantity');
                return $first;
            })->values();
            
        return response()->json($supplies);
    }
    
    public function adjustStock(Request $request, $id)
    {
        $request->validate([
            'adjustment' => 'required|integer',
            'remarks' => 'required|string|max:255'
        ]);

        $clickedItem = Supply::findOrFail($id);
        $adjustment = (int) $request->input('adjustment');
        $reason = strtoupper($request->input('remarks'));

        if ($adjustment == 0) {
            return redirect()->back()->with('danger', 'Adjustment amount cannot be zero.');
        }

        $matchingSupplies = Supply::where('name', $clickedItem->name)->orderBy('id', 'asc')->get();
        $batchId = 'ADJ-' . strtoupper(\Illuminate\Support\Str::random(5));

        if ($adjustment > 0) {
            $clickedItem->quantity += $adjustment;
            $clickedItem->save();
            
            \App\Models\DepartmentRequest::create([
                'batch_id' => $batchId,
                'department_name' => 'SYSTEM RESTOCK',
                'requested_by' => $reason,
                'supply_id' => $clickedItem->id,
                'quantity' => -$adjustment, 
                'purpose' => 'Positive Stock Adjustment',
                'status' => 'Approved',
                'issued_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            \App\Models\ActivityLog::log('Inventory', "Adjusted stock UP for {$clickedItem->name} by {$adjustment}. Reason: {$reason}");
        } 
        else {
            $deductAmount = abs($adjustment);
            $totalCombinedStock = $matchingSupplies->sum('quantity');

            if ($totalCombinedStock < $deductAmount) {
                return redirect()->back()->with('danger', "Cannot deduct {$deductAmount}. Total combined stock for '{$clickedItem->name}' across all entries is only {$totalCombinedStock}!");
            }

            $remainingToDeduct = $deductAmount;

            foreach ($matchingSupplies as $supply) {
                if ($remainingToDeduct <= 0) break;

                if ($supply->quantity > 0) {
                    $deductedFromThis = min($supply->quantity, $remainingToDeduct);
                    
                    $supply->quantity -= $deductedFromThis;
                    $supply->save();

                    \App\Models\DepartmentRequest::create([
                        'batch_id' => $batchId,
                        'department_name' => 'SYSTEM ADJUSTMENT',
                        'requested_by' => $reason,
                        'supply_id' => $supply->id,
                        'quantity' => $deductedFromThis,
                        'purpose' => 'Combined Stock Deduction',
                        'status' => 'Approved',
                        'issued_by' => \Illuminate\Support\Facades\Auth::id(),
                    ]);

                    $remainingToDeduct -= $deductedFromThis;
                }
            }
            
            \App\Models\ActivityLog::log('Inventory', "Adjusted combined stock DOWN for {$clickedItem->name} by {$deductAmount}. Reason: {$reason}");
        }

        return redirect('/inventory')
            ->with('success', "Stock successfully adjusted for {$clickedItem->name}!")
            ->with('active_category', $clickedItem->category);
    }

    public function printItemUsage(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:supplies,id',
            'month' => 'required',
            'year' => 'required',
        ]);

        $item = Supply::findOrFail($request->item_id);
        
        $usageData = \App\Models\DepartmentRequest::where('supply_id', $item->id)
            ->where('status', 'Approved')
            ->where('quantity', '>', 0)
            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT']) 
            ->whereMonth('updated_at', $request->month)
            ->whereYear('updated_at', $request->year)
            ->selectRaw('department_name, SUM(quantity) as total_requested')
            ->groupBy('department_name')
            ->orderBy('total_requested', 'desc')
            ->get();

        $reportMonth = Carbon::createFromFormat('m', $request->month)->format('F');
        $reportYear = $request->year;

        return view('print_item_usage', compact('item', 'usageData', 'reportMonth', 'reportYear'));
    }

    public function lowStockApi()
    {
        $role = Auth::user()->role;
        $query = Supply::whereRaw('quantity <= reorder_level');

        if ($role === 'forms_admin') {
            $query->where('category', 'Forms');
        } elseif ($role === 'admin') {
            $query->where(function($q) {
                $q->where('category', '!=', 'Forms')->orWhereNull('category');
            });
        }

        $criticalItems = $query->select('name', 'quantity', 'reorder_level')
            ->orderBy('quantity', 'asc')
            ->get();

        return response()->json([
            'count' => $criticalItems->count(),
            'items' => $criticalItems
        ]);
    }
    public function generateCriticalPR()
    {
        $criticalItems = \App\Models\Supply::whereRaw('quantity <= reorder_level')
            ->where('category', 'Office Supplies')
            ->orderBy('name', 'asc')
            ->get();

        if ($criticalItems->isEmpty()) {
            return redirect()->back()->with('success', 'All Office Supplies stocks are healthy. No PR needed.');
        }

        \App\Models\ActivityLog::log('Inventory', "Generated printable Purchase Request for Office Supplies.");

        return view('print_critical_pr', compact('criticalItems'));
    }
}