<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supply;
use App\Models\DepartmentRequest;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $role = strtolower(trim(Auth::user()->role));
        
        $supplyQuery = Supply::query();

        if ($role === 'forms_admin') {
            $supplyQuery->where('category', 'like', '%Form%');
        } elseif ($role === 'admin') {
            $supplyQuery->where(function($q) {
                $q->where('category', 'not like', '%Form%')->orWhereNull('category');
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
            $reqsQuery = DepartmentRequest::whereDate('created_at', $date)
                ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY']);

            if ($role === 'forms_admin') {
                $reqsQuery->whereHas('supply', function($q) {
                    $q->where('category', 'LIKE', '%form%');
                });
            } elseif ($role === 'admin') {
                $reqsQuery->where(function($q) {
                    $q->whereHas('supply', function($subQ) {
                        $subQ->where('category', 'NOT LIKE', '%form%')->orWhereNull('category');
                    })->orWhereDoesntHave('supply');
                });
            }
            
            $chartDates[] = \Carbon\Carbon::now()->subDays($i)->format('M d');
            $chartData[] = $reqsQuery->distinct('batch_id')->count('batch_id');
        }

        $allRequests = DepartmentRequest::with(['supply', 'issuer'])
            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY'])
            ->where('batch_id', 'not like', 'ADJ-%')
            ->where('batch_id', 'not like', 'DEL-%')
            ->orderByRaw("FIELD(status, 'Pending', 'Approved', 'Denied')")
            ->orderByRaw("CASE WHEN status = 'Pending' THEN created_at END ASC")
            ->orderByRaw("CASE WHEN status != 'Pending' THEN created_at END DESC")
            ->get();
        
        $groupedBatches = [];
        
        foreach ($allRequests as $req) {
            // PROVEN SPLITTER: Explicit uppercase check guarantees no typos
            $cat = $req->supply ? strtoupper(trim($req->supply->category ?? '')) : '';
            $isForm = (strpos($cat, 'FORM') !== false);
            
            if ($role === 'forms_admin' && !$isForm) continue;
            if ($role === 'admin' && $isForm) continue;

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

        foreach ($groupedBatches as $key => $batch) {
            $hasPending = false;
            foreach ($batch['items'] as $item) {
                if ($item->status === 'Pending') {
                    $hasPending = true;
                    break;
                }
            }
            $groupedBatches[$key]['status'] = $hasPending ? 'Pending' : 'Processed';
        }

        $requestsToDisplay = array_values($groupedBatches);
        $pendingCount = collect($requestsToDisplay)->where('status', 'Pending')->count();
        
        // BUG FIX: Increased pagination to 50 so new requests don't vanish onto Page 2!
        $perPage = 50; 
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

        $trimmedName = trim($validated['name']);

        // Check if an item with the same name already exists (case-insensitive & trimmed)
        $existingItem = Supply::withTrashed()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($trimmedName)])
            ->first();

        if ($existingItem) {
            if ($existingItem->trashed()) {
                $existingItem->restore();
            }

            $addedQty = (int)$validated['quantity'];
            $existingItem->quantity += $addedQty;

            // Merge RIS numbers cleanly without duplicates
            if (!empty($validated['ris_number'])) {
                $currentRis = array_filter(array_map('trim', explode(',', (string)$existingItem->ris_number)));
                $incomingRis = array_filter(array_map('trim', explode(',', (string)$validated['ris_number'])));
                $mergedRis = array_unique(array_merge($currentRis, $incomingRis));
                $existingItem->ris_number = implode(', ', $mergedRis);
            }

            // Update supplier if provided
            if (!empty($validated['supplier'])) {
                $existingItem->supplier = $validated['supplier'];
            }

            // Update unit price if provided
            if (isset($validated['unit_price']) && $validated['unit_price'] !== null && $validated['unit_price'] !== '') {
                $existingItem->unit_price = $validated['unit_price'];
            }

            // Update delivery date if provided
            if (!empty($validated['date_delivered'])) {
                $existingItem->date_delivered = $validated['date_delivered'];
            }

            // Update expiry date if provided
            if (!empty($validated['expiry_date'])) {
                $existingItem->expiry_date = $validated['expiry_date'];
            }

            // Fill in category or description if existing was empty
            if (empty($existingItem->category) && !empty($validated['category'])) {
                $existingItem->category = $validated['category'];
            }
            if (empty($existingItem->description) && !empty($validated['description'])) {
                $existingItem->description = $validated['description'];
            }

            if ($request->filled('unit') && empty($existingItem->unit)) {
                $existingItem->unit = $request->input('unit');
            }

            $existingItem->is_visible = 1;
            $existingItem->save();

            // Record incoming delivery transaction to preserve historical ledgers
            if ($addedQty > 0) {
                $deliveryDate = !empty($validated['date_delivered']) 
                    ? \Carbon\Carbon::parse($validated['date_delivered'])->setTimeFrom(now()) 
                    : now();

                $risNumber = !empty($validated['ris_number']) ? trim($validated['ris_number']) : null;
                $supplierName = !empty($validated['supplier']) ? trim($validated['supplier']) : ($existingItem->supplier ?: 'Supplier Delivery');

                $deliveryRecord = new \App\Models\DepartmentRequest();
                $deliveryRecord->batch_id = $risNumber ?: ('DEL-' . strtoupper(\Illuminate\Support\Str::random(5)));
                $deliveryRecord->department_name = 'DELIVERY';
                $deliveryRecord->requested_by = $supplierName;
                $deliveryRecord->supply_id = $existingItem->id;
                $deliveryRecord->quantity = -$addedQty; // Negative qty designates incoming stock receipt
                $deliveryRecord->purpose = 'New Delivery Restock' . ($risNumber ? " (RIS: {$risNumber})" : '');
                $deliveryRecord->status = 'Approved';
                $deliveryRecord->is_emergency = 0;
                $deliveryRecord->issued_by = \Illuminate\Support\Facades\Auth::id();
                $deliveryRecord->created_at = $deliveryDate;
                $deliveryRecord->updated_at = $deliveryDate;
                $deliveryRecord->save();
            }

            \App\Models\ActivityLog::log('Inventory', "Restocked existing item: {$existingItem->name} (+{$addedQty} {$existingItem->unit}, New Total: {$existingItem->quantity})");

            return redirect('/inventory')
                ->with('success', "Delivery added to existing stock for {$existingItem->name}! Added +{$addedQty} {$existingItem->unit} (New Total: {$existingItem->quantity}).")
                ->with('active_category', $existingItem->category);
        }

        $validated['name'] = $trimmedName;
        $newItem = Supply::create($validated);

        \App\Models\ActivityLog::log('Inventory', "Added new item: {$newItem->name} (Qty: {$newItem->quantity})");

        return redirect('/inventory')
            ->with('success', "New item '{$newItem->name}' added successfully!")
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
        $role = strtolower(trim(Auth::user()->role));
        
        $allReqs = DepartmentRequest::with('supply')->where('batch_id', $batch_id)->get();
        $batchReqs = collect();

        foreach ($allReqs as $req) {
            $cat = $req->supply ? strtoupper(trim($req->supply->category ?? '')) : '';
            $isForm = (strpos($cat, 'FORM') !== false);
            
            if ($role === 'forms_admin' && $isForm) $batchReqs->push($req);
            if ($role === 'admin' && !$isForm) $batchReqs->push($req);
        }

        if ($batchReqs->isEmpty()) {
            return redirect()->back()->with('danger', 'No authorized items found for approval in this request.');
        }

        $pendingReqs = $batchReqs->where('status', 'Pending');
        
        if ($pendingReqs->isEmpty()) {
            return redirect()->back()->with('danger', 'These specific items have already been processed.');
        }

        $isEmergency = $pendingReqs->first()->is_emergency;
        $batchTime = $pendingReqs->first()->created_at;
        
        if (!$isEmergency && $role === 'admin') {
            $allPending = DepartmentRequest::with('supply')
                ->where('status', 'Pending')
                ->where('created_at', '<', $batchTime)
                ->orderBy('created_at', 'asc')
                ->get();
                
            $blockingReq = null;
            foreach ($allPending as $req) {
                $cat = $req->supply ? strtoupper(trim($req->supply->category ?? '')) : '';
                $isForm = (strpos($cat, 'FORM') !== false);
                
                if (!$isForm) {
                    $blockingReq = $req;
                    break;
                }
            }

            if ($blockingReq) {
                $oldestTime = \Carbon\Carbon::parse($blockingReq->created_at)->format('h:i A');
                return redirect()->back()->with('danger', "Strict FIFO Active: You cannot process this yet! You must first process your older pending request from {$blockingReq->department_name} submitted at {$oldestTime}.");
            }
        }

        foreach ($pendingReqs as $req) {
            $adjQty = $request->input("qty_{$req->id}");
            if ($adjQty !== null) {
                $req->quantity = (int)$adjQty;
            }
        }

        $supplySums = [];
        foreach ($pendingReqs as $req) {
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
        
        DB::transaction(function () use ($pendingReqs) {
            foreach ($pendingReqs as $req) {
                $remainingToDeduct = $req->quantity;
                
                if ($remainingToDeduct <= 0) {
                    if (!empty($req->id)) {
                        DB::table('department_requests')->where('id', $req->id)->update([
                            'status' => 'Denied',
                            'quantity' => 0,
                            'updated_at' => now()
                        ]);
                    }
                    continue; 
                }
                
                $itemName = $req->supply->name;
                
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
                        if (!empty($req->id)) {
                            DB::table('department_requests')->where('id', $req->id)->update([
                                'supply_id' => $supply->id,
                                'quantity' => $deductAmount,
                                'status' => 'Approved',
                                'issued_by' => Auth::id(),
                                'updated_at' => now()
                            ]);
                        }
                        $firstMatch = false;
                    } else {
                        DepartmentRequest::create([
                            'batch_id' => $req->batch_id,
                            'department_name' => $req->department_name,
                            'requested_by' => $req->requested_by,
                            'supply_id' => $supply->id,
                            'quantity' => $deductAmount,
                            'purpose' => $req->purpose,
                            'is_emergency' => $req->is_emergency,
                            'status' => 'Approved',
                            'issued_by' => Auth::id(),
                            'created_at' => $req->created_at,
                            'updated_at' => now()
                        ]);
                    }
                    
                    $remainingToDeduct -= $deductAmount;
                }
            }
        });

        $departmentName = $pendingReqs->first()->department_name;
        \App\Models\ActivityLog::log('Supply Issuance', "Approved and issued request {$batch_id} to {$departmentName}");

        return redirect()->back()->with('success', 'Your department items in this request were approved!');
    }

    public function denyBatch($batch_id)
    {
        $role = strtolower(trim(Auth::user()->role));
        
        $allReqs = DepartmentRequest::with('supply')->where('batch_id', $batch_id)->get();
        $batchReqs = collect();

        foreach ($allReqs as $req) {
            $cat = $req->supply ? strtoupper(trim($req->supply->category ?? '')) : '';
            $isForm = (strpos($cat, 'FORM') !== false);
            
            if ($role === 'forms_admin' && $isForm) $batchReqs->push($req);
            if ($role === 'admin' && !$isForm) $batchReqs->push($req);
        }

        foreach ($batchReqs as $req) {
            if (!empty($req->id)) {
                DB::table('department_requests')->where('id', $req->id)->update([
                    'status' => 'Denied',
                    'updated_at' => now()
                ]);
            }
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
        $role = strtolower(trim(Auth::user()->role));
        $allPending = DepartmentRequest::with('supply')->where('status', 'Pending')->get();
        
        $countedBatches = [];
        $count = 0;
        
        foreach ($allPending as $req) {
            $cat = $req->supply ? strtoupper(trim($req->supply->category ?? '')) : '';
            $isForm = (strpos($cat, 'FORM') !== false);
            
            if ($role === 'forms_admin' && !$isForm) continue;
            if ($role === 'admin' && $isForm) continue;
            
            if (!in_array($req->batch_id, $countedBatches)) {
                $countedBatches[] = $req->batch_id;
                $count++;
            }
        }
        
        return response()->json(['count' => $count]);
    }
    
    public function inventory() 
    {
        $role = strtolower(trim(Auth::user()->role));
        $query = Supply::orderBy('name', 'asc');
        
        if ($role === 'forms_admin') {
            $query->where('category', 'like', '%Form%');
        } elseif ($role === 'admin') {
            $query->where(function($q) {
                $q->where('category', 'not like', '%Form%')->orWhereNull('category');
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
        $role = strtolower(trim(Auth::user()->role));

        $query = Supply::query();

        $query->withSum(['departmentRequests' => function ($q) use ($month, $year) {
            $q->where('status', 'Approved')
              ->where('quantity', '>', 0)
              ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY']);
              
            if ($month && $year) {
                $q->whereMonth('updated_at', $month)
                  ->whereYear('updated_at', $year);
            }
        }], 'quantity');

        if ($role === 'forms_admin') {
            $query->where('category', 'like', '%Form%');
        } elseif ($request->has('category') && $request->category !== 'ALL') {
            $query->where('category', $request->category);
        } else {
            $query->where(function($q) {
                $q->where('category', 'not like', '%Form%')->orWhereNull('category');
            });
        }

        $supplies = $query->orderBy('name', 'asc')->get();

        if ($month && $year) {
            $startOfMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endOfMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
            
            foreach ($supplies as $supply) {
                $futureTransactions = \App\Models\DepartmentRequest::where('supply_id', $supply->id)
                    ->where('status', 'Approved')
                    ->where('quantity', '>', 0) 
                    ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY'])
                    ->where('updated_at', '>', $endOfMonth)
                    ->sum('quantity');

                $futureDeliveries = abs(\App\Models\DepartmentRequest::where('supply_id', $supply->id)
                    ->where('status', 'Approved')
                    ->where('department_name', 'DELIVERY')
                    ->where('updated_at', '>', $endOfMonth)
                    ->sum('quantity'));

                $monthDeliveries = abs(\App\Models\DepartmentRequest::where('supply_id', $supply->id)
                    ->where('status', 'Approved')
                    ->where('department_name', 'DELIVERY')
                    ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                    ->sum('quantity'));

                $supply->quantity = $supply->quantity + $futureTransactions - $futureDeliveries;
                $supply->deliveries_sum_quantity = $monthDeliveries;
            }
        }
        
        $supplies = $supplies->groupBy('name')->map(function ($group) use ($month, $year) {
            $first = $group->first();
            $first->quantity = $group->sum('quantity');
            $first->department_requests_sum_quantity = $group->sum('department_requests_sum_quantity');
            $first->deliveries_sum_quantity = $group->sum('deliveries_sum_quantity');

            if ($month && $year) {
                // Beginning Balance = Ending Available Stock - Deliveries in Month + Issuances in Month
                $first->beginning_balance = $first->quantity - (int)$first->deliveries_sum_quantity + (int)$first->department_requests_sum_quantity;
            } else {
                $first->beginning_balance = $first->quantity;
            }

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
        $monthFilter = $request->input('month', Carbon::now()->format('m'));
        $yearFilter = $request->input('year', Carbon::now()->format('Y'));
        $startOfMonth = "$yearFilter-$monthFilter-01 00:00:00";
        
        $releases = DepartmentRequest::where('supply_id', $id)
            ->where('status', 'Approved')
            ->where(function($q) {
                $q->where(function($sub) {
                    $sub->where('quantity', '>', 0)
                        ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY']);
                })
                ->orWhere('department_name', 'DELIVERY');
            })
            ->whereMonth('updated_at', $monthFilter)
            ->whereYear('updated_at', $yearFilter)
            ->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
        $filename = "Stockcard_{$item->name}_{$yearFilter}_{$monthFilter}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        $columns = ['Date', 'Reference', 'From Whom Received / To Whom Issued', 'Qty Received', 'Qty Issued', 'Running Balance'];

        $callback = function () use ($releases, $columns, $item, $startOfMonth) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            $futureAndCurrentIssued = DepartmentRequest::where('supply_id', $item->id)
                ->where('status', 'Approved')
                ->where('quantity', '>', 0) 
                ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY'])
                ->where('updated_at', '>=', $startOfMonth)
                ->sum('quantity');

            $futureAndCurrentDeliveries = abs(DepartmentRequest::where('supply_id', $item->id)
                ->where('status', 'Approved')
                ->where('department_name', 'DELIVERY')
                ->where('updated_at', '>=', $startOfMonth)
                ->sum('quantity'));

            $balance = $item->quantity + $futureAndCurrentIssued - $futureAndCurrentDeliveries;

            fputcsv($file, [
                Carbon::parse($startOfMonth)->format('m/d/Y'),
                '',
                'BALANCE FORWARDED',
                '',
                '',
                $balance
            ]);

            foreach ($releases as $release) {
                if ($release->quantity < 0) {
                    $balance += abs($release->quantity);
                    $qtyReceived = abs($release->quantity);
                    $qtyIssued = '';
                } else {
                    $balance -= $release->quantity;
                    $qtyReceived = '';
                    $qtyIssued = $release->quantity;
                }
                
                fputcsv($file, [
                    Carbon::parse($release->updated_at ?: $release->created_at)->format('m/d/Y'),
                    strtoupper(substr($release->batch_id, 0, 8)),
                    $release->department_name . ' (' . $release->requested_by . ')',
                    $qtyReceived,
                    $qtyIssued,
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
        $role = strtolower(trim(Auth::user()->role));
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
                ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY'])
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
            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY'])
            ->where('batch_id', 'not like', 'ADJ-%')
            ->where('batch_id', 'not like', 'DEL-%')
            ->orderByRaw("FIELD(status, 'Pending', 'Approved', 'Denied')")
            ->orderByRaw("CASE WHEN status = 'Pending' THEN created_at END ASC")
            ->orderByRaw("CASE WHEN status != 'Pending' THEN created_at END DESC")
            ->get();
            
        $groupedBatches = [];
        foreach ($allRequests as $req) {
            $cat = $req->supply ? strtoupper(trim($req->supply->category ?? '')) : '';
            $isForm = (strpos($cat, 'FORM') !== false);
            
            if ($role === 'forms_admin' && !$isForm) continue;
            if ($role === 'admin' && $isForm) continue;

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

        foreach ($groupedBatches as $key => $batch) {
            $hasPending = false;
            foreach ($batch['items'] as $item) {
                if ($item->status === 'Pending') {
                    $hasPending = true;
                    break;
                }
            }
            $groupedBatches[$key]['status'] = $hasPending ? 'Pending' : 'Processed';
        }

        $requestsCollection = collect(array_values($groupedBatches));

        $pending_count = $requestsCollection->where('status', 'Pending')->count();
        $perPage = 50;
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
                ->where(function($q) {
                    $q->where(function($sub) {
                        $sub->where('quantity', '>', 0)
                            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY']);
                    })
                    ->orWhere('department_name', 'DELIVERY');
                })
                ->whereMonth('updated_at', $month_filter)
                ->whereYear('updated_at', $year_filter)
                ->orderBy('updated_at', 'asc')
                ->orderBy('id', 'asc') 
                ->get();

            $startOfMonth = $year_filter . '-' . $month_filter . '-01 00:00:00';

            $futureAndCurrentIssued = DepartmentRequest::where('supply_id', $supply->id)
                ->where('status', 'Approved')
                ->where('quantity', '>', 0) 
                ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY'])
                ->where('updated_at', '>=', $startOfMonth)
                ->sum('quantity');

            $futureAndCurrentDeliveries = abs(DepartmentRequest::where('supply_id', $supply->id)
                ->where('status', 'Approved')
                ->where('department_name', 'DELIVERY')
                ->where('updated_at', '>=', $startOfMonth)
                ->sum('quantity'));

            $balance_forwarded = $supply->quantity + $futureAndCurrentIssued - $futureAndCurrentDeliveries;
            $tempBalance = $balance_forwarded;
            $formattedReleases = collect();
            
            foreach ($releasesAsc as $release) {
                if ($release->quantity < 0) {
                    $tempBalance += abs($release->quantity);
                } else {
                    $tempBalance -= $release->quantity;
                }
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
        
        $transactionsQuery = \App\Models\DepartmentRequest::where('supply_id', $item->id)
            ->where('status', 'Approved')
            ->where(function($q) {
                $q->where(function($sub) {
                    $sub->where('quantity', '>', 0)
                        ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY']);
                })
                ->orWhere('department_name', 'DELIVERY');
            })
            ->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalIssuedEver = $transactionsQuery->where('quantity', '>', 0)->sum('quantity');
        $totalDeliveriesEver = abs($transactionsQuery->where('quantity', '<', 0)->sum('quantity'));
        
        $startingBalance = $item->quantity + $totalIssuedEver - $totalDeliveriesEver;

        $transactions = collect();
        $transactions->push([
            'date' => $item->date_delivered ? $item->date_delivered : $item->created_at,
            'department' => $item->supplier ?: 'Initial Inventory',
            'delivery_qty' => $startingBalance,
            'qty_issued' => '',
            'balance' => $startingBalance
        ]);

        $currentBalance = $startingBalance;
        foreach ($transactionsQuery as $tx) {
            $txDate = $tx->updated_at ?: $tx->created_at;
            if ($tx->quantity < 0) {
                $deliveryQty = abs($tx->quantity);
                $currentBalance += $deliveryQty;
                $transactions->push([
                    'date' => $txDate,
                    'department' => $tx->requested_by ?: 'Delivery',
                    'delivery_qty' => $deliveryQty,
                    'qty_issued' => '',
                    'balance' => $currentBalance
                ]);
            } else {
                $currentBalance -= $tx->quantity;
                $transactions->push([
                    'date' => $txDate,
                    'department' => $tx->department_name,
                    'delivery_qty' => '',
                    'qty_issued' => $tx->quantity,
                    'balance' => $currentBalance
                ]);
            }
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
            $transactionsThisYear = \App\Models\DepartmentRequest::where('supply_id', $item->id)
                ->where('status', 'Approved')
                ->where(function($q) {
                    $q->where(function($sub) {
                        $sub->where('quantity', '>', 0)
                            ->whereNotIn('department_name', ['SYSTEM RESTOCK', 'SYSTEM ADJUSTMENT', 'DELIVERY']);
                    })
                    ->orWhere('department_name', 'DELIVERY');
                })
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
                    $endOfMonth = \Carbon\Carbon::createFromDate($currentYear, $m, 1)->endOfMonth();

                    $issuancesAfterM = $transactionsThisYear->filter(function($req) use ($endOfMonth) {
                        $txDate = $req->updated_at ?: $req->created_at;
                        return $req->quantity > 0 && \Carbon\Carbon::parse($txDate) > $endOfMonth;
                    })->sum('quantity');

                    $deliveriesAfterM = abs($transactionsThisYear->filter(function($req) use ($endOfMonth) {
                        $txDate = $req->updated_at ?: $req->created_at;
                        return $req->quantity < 0 && \Carbon\Carbon::parse($txDate) > $endOfMonth;
                    })->sum('quantity'));

                    $monthlyBalances[$m] = $item->quantity + $issuancesAfterM - $deliveriesAfterM;
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
        $role = strtolower(trim(Auth::user()->role));
        $query = Supply::whereRaw('quantity <= reorder_level');

        if ($role === 'forms_admin') {
            $query->where('category', 'like', '%Form%');
        } elseif ($role === 'admin') {
            $query->where(function($q) {
                $q->where('category', 'not like', '%Form%')->orWhereNull('category');
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