<x-layouts.admin title="Inventory Management | Supply Hub">

    {{-- ADMIN TOOLBAR --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-box-seam-fill text-primary me-2"></i> Inventory Command Center
        </h5>
        
        <div class="d-flex flex-wrap gap-2 align-items-center">
            {{-- 1. Petty Cash Ledger --}}
            <a href="/petty-cash" class="btn btn-sm btn-outline-danger btn-modern fw-bold shadow-sm">
                <i class="bi bi-wallet2 me-1"></i> Petty Cash
            </a>

            {{-- 2. Item Usage Report --}}
            <button type="button" class="btn btn-sm btn-outline-info text-dark btn-modern fw-bold shadow-sm border-info" data-bs-toggle="modal" data-bs-target="#itemUsageModal">
                <i class="bi bi-graph-up me-1"></i> Item Usage
            </button>

            {{-- 3. Monthly Report --}}
            <button type="button" class="btn btn-sm btn-outline-secondary btn-modern fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#monthlyReportModal">
                <i class="bi bi-calendar-check me-1"></i> Monthly Report
            </button>

            {{-- 4. Print Directory (Silent Print) --}}
            <button type="button" class="btn btn-sm btn-outline-dark btn-modern fw-bold shadow-sm" onclick="printDirectly('/print-inventory')">
                <i class="bi bi-printer-fill me-1"></i> Print Directory
            </button>

            {{-- Separator --}}
            <div class="vr mx-1 d-none d-md-block text-secondary opacity-25"></div>

            {{-- 5. Add Item (Main Action) --}}
            <button type="button" class="btn btn-sm btn-primary btn-modern shadow-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="bi bi-plus-lg me-1"></i> Add Supply
            </button>
        </div>
    </div>
    
    {{-- DYNAMIC DATA CALCULATION --}}
    @php
        // 1. Total Asset Value
        $totalAssetValue = $supplies->sum(function($item) {
            return $item->quantity * ($item->unit_price ?? 0);
        });

        // 2. Stock Health Logic
        $totalItems = $supplies->count();
        $lowStockCount = 0;
        foreach($supplies as $s) {
            if($s->quantity <= $s->reorder_level) $lowStockCount++;
        }
        $goodStockCount = $totalItems - $lowStockCount;
        $healthPercentage = $totalItems > 0 ? round(($goodStockCount / $totalItems) * 100) : 0;

        // 3. Category Distribution for Donut Chart
        $categoryData = $supplies->groupBy(function($item) {
            return $item->category ?: 'Uncategorized';
        })->map->count();
        
        // NEW: Fetch master categories EXACTLY ONCE to prevent lag
        $masterCategories = \Illuminate\Support\Facades\DB::table('categories')->get();
    @endphp
    
    <style>
        /* Pulse Animation for Critical Stock */
        @keyframes pulse-danger {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .pulse-danger {
            animation: pulse-danger 2s infinite;
        }
        
        /* Action Button Consistency */
        .btn-action {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 8px;
        }
    </style>

    @if(session('success'))
        <div class="alert alert-success shadow-sm rounded-4 border-0 mb-4 py-3">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i> 
            <span class="fw-bold">{{ session('success') }}</span>
        </div>
    @endif

    <!-- COMMAND CENTER ANALYTICS HEADER -->
    <div class="row g-4 mb-5">
        {{-- CARD 1: Total Asset Value --}}
        <div class="col-md-4">
            <div class="bento-card bg-primary bg-opacity-10 border border-primary border-opacity-25 d-flex flex-column justify-content-center h-100">
                <p class="text-primary fw-bold mb-1 text-uppercase small"><i class="bi bi-cash-stack me-1"></i> Estimated Asset Value</p>
                <h2 class="fw-bolder mb-0 text-primary">₱{{ number_format($totalAssetValue, 2) }}</h2>
                <div class="mt-2 text-primary opacity-75 small fw-bold">Based on current unit prices</div>
            </div>
        </div>
        
        {{-- CARD 2: Stock Health Progress --}}
        <div class="col-md-4">
            <div class="bento-card bg-body-tertiary border border-secondary-subtle d-flex flex-column justify-content-center h-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <p class="text-muted fw-bold mb-0 text-uppercase small"><i class="bi bi-heart-pulse-fill text-danger me-1"></i> Stock Health</p>
                    <span class="badge {{ $healthPercentage < 50 ? 'bg-danger' : 'bg-success' }}">{{ $healthPercentage }}%</span>
                </div>
                <h2 class="fw-bolder mb-2 text-dark">{{ $goodStockCount }} <span class="fs-6 fw-normal text-muted">/ {{ $totalItems }} items healthy</span></h2>
                <div class="progress" style="height: 6px; border-radius: 10px;">
                    <div class="progress-bar {{ $healthPercentage < 50 ? 'bg-danger' : 'bg-success' }}" style="width: {{ $healthPercentage }}%"></div>
                </div>
            </div>
        </div>

        {{-- CARD 3: Category Distribution Donut --}}
        <div class="col-md-4">
            <div class="bento-card bg-body border border-secondary-subtle position-relative p-2 h-100 d-flex align-items-center justify-content-center">
                <div id="categoryDonutChart" class="w-100" style="min-height: 120px;"></div>
            </div>
        </div>
    </div>

    <!-- INVENTORY HEATMAP TABLE -->
    <div class="bento-card mb-5">
        
        {{-- UPGRADED DUAL-FILTER HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bolder mb-0"><i class="bi bi-list-columns-reverse text-info me-2"></i> Stock Heatmap</h5>
            
            <div class="d-flex gap-2" style="max-width: 450px; width: 100%;">
                <select id="categoryFilter" class="form-select input-modern rounded-4 border-secondary-subtle shadow-sm" style="width: 40%;" onchange="filterInventory()">
                    <option value="all">All Categories</option>
                    @foreach($categoryData->keys() as $cat)
                        <option value="{{ strtolower(trim($cat)) }}">{{ strtoupper($cat) }}</option>
                    @endforeach
                </select>
                
                <div class="input-group" style="width: 60%;">
                    <span class="input-group-text bg-body-tertiary border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="inventorySearch" class="form-control input-modern border-start-0 rounded-end-4 pl-0 shadow-sm" placeholder="Search items..." onkeyup="filterInventory()">
                </div>
            </div>
        </div>

        <div class="table-responsive" style="max-height: 650px; overflow-y: auto;">
            <table class="table table-clean align-middle mb-0">
                <thead class="sticky-top bg-body z-1 border-bottom border-secondary-subtle">
                    <tr>
                        <th class="bg-body text-uppercase small text-muted">Item Details</th>
                        <th class="bg-body text-uppercase small text-muted text-center" style="width: 10%;">Price</th>
                        <th class="bg-body text-uppercase small text-muted" style="width: 30%;">Stock Heatmap</th>
                        <th class="bg-body text-uppercase small text-muted text-end" style="width: 35%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplies as $item)
                        {{-- CRITICAL FIX: The data-category attribute is securely added here --}}
                        <tr class="inv-row" data-category="{{ strtolower(trim($item->category ?: 'uncategorized')) }}">
                            <td>
                                <div class="fw-bolder text-dark fs-6">{{ $item->name }}</div>
                                <div class="text-muted-soft small text-uppercase mt-1" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-tag-fill me-1"></i>{{ $item->category ?: 'Uncategorized' }}
                                    
                                    {{-- NEW: Supplier Display --}}
                                    @if($item->supplier)
                                        <span class="mx-1 text-secondary opacity-50">|</span>
                                        <i class="bi bi-building text-primary opacity-75 me-1"></i>{{ $item->supplier }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark">₱{{ number_format($item->unit_price ?? 0, 2) }}</span>
                            </td>
                            <td>
                                {{-- HEATMAP LOGIC --}}
                                @php
                                    $maxVisualStock = $item->reorder_level > 0 ? $item->reorder_level * 3 : 100;
                                    $fillPercentage = $maxVisualStock > 0 ? min(100, ($item->quantity / $maxVisualStock) * 100) : 0;
                                    
                                    if ($item->quantity <= 0) {
                                        $barColor = 'bg-danger'; $pulse = 'pulse-danger'; $text = 'text-danger'; $status = 'Out of Stock';
                                    } elseif ($item->quantity <= $item->reorder_level) {
                                        $barColor = 'bg-danger'; $pulse = 'pulse-danger'; $text = 'text-danger'; $status = 'Critical';
                                    } elseif ($item->quantity <= $item->reorder_level * 1.5) {
                                        $barColor = 'bg-warning'; $pulse = ''; $text = 'text-warning'; $status = 'Low';
                                    } else {
                                        $barColor = 'bg-success'; $pulse = ''; $text = 'text-success'; $status = 'Healthy';
                                    }
                                @endphp

                                <div class="d-flex justify-content-between align-items-end mb-1">
                                    <span class="fw-bolder fs-5 {{ $text }}">{{ $item->quantity }} <span class="fs-6 text-muted fw-normal">{{ $item->unit ?? 'pcs' }}</span></span>
                                    <span class="small fw-bold {{ $text }}">{{ $status }}</span>
                                </div>
                                <div class="progress bg-body-tertiary border border-secondary-subtle overflow-visible" style="height: 8px; border-radius: 10px;">
                                    <div class="progress-bar {{ $barColor }} {{ $pulse }}" style="width: {{ $fillPercentage }}%; border-radius: 10px;" title="Reorder Level: {{ $item->reorder_level }}"></div>
                                </div>
                                <div class="text-end mt-1 text-muted-soft" style="font-size: 0.65rem;">Alert Level: {{ $item->reorder_level }}</div>
                            </td>
                            <td class="text-end">
                                
                                {{-- 7-BUTTON ACTION ARRAY (FIXED) --}}
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    {{-- 1. Visibility --}}
                                    <form action="/inventory/toggle-visibility/{{ $item->id }}" method="POST" class="d-inline m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-action btn-success text-white shadow-sm" title="Toggle Visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </form>

                                    {{-- 2. Adjust Stock --}}
                                    <button type="button" class="btn btn-action btn-body-tertiary border border-secondary-subtle shadow-sm text-warning" data-bs-toggle="modal" data-bs-target="#adjustModal-{{ $item->id }}" title="Adjust Stock">
                                        <i class="bi bi-sliders"></i>
                                    </button>

                                    {{-- 3. Stockcard (Teal List) - SILENT PRINT --}}
                                    <button type="button" onclick="printDirectly('/stockcard/{{ $item->id }}')" class="btn btn-action btn-body-tertiary border border-secondary-subtle shadow-sm" style="color: #20c997;" title="Print Stockcard">
                                        <i class="bi bi-card-list"></i>
                                    </button>

                                    {{-- 4. RAPC (Blue Document) - SILENT PRINT --}}
                                    <button type="button" onclick="printDirectly('/rapc/{{ $item->id }}')" class="btn btn-action btn-body-tertiary border border-secondary-subtle shadow-sm text-info" title="Print RAPC">
                                        <i class="bi bi-file-earmark-spreadsheet"></i>
                                    </button>

                                    {{-- 5. Bincard (Dark Printer) - SILENT PRINT --}}
                                    <button type="button" onclick="printDirectly('/bincard/{{ $item->id }}')" class="btn btn-action btn-body-tertiary border border-secondary-subtle shadow-sm text-dark" title="Print Bincard">
                                        <i class="bi bi-printer"></i>
                                    </button>

                                    {{-- 6. Edit --}}
                                    <button type="button" class="btn btn-action btn-body-tertiary border border-secondary-subtle shadow-sm text-primary" data-bs-toggle="modal" data-bs-target="#editModal-{{ $item->id }}" title="Edit Item">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    {{-- 7. Delete --}}
                                    <a href="/delete/{{ $item->id }}" class="btn btn-action btn-body-tertiary border border-secondary-subtle shadow-sm text-danger" onclick="return confirm('Are you sure you want to permanently delete this item?')" title="Delete Item">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted fw-medium">
                                <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-25"></i>
                                Inventory is currently empty.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- SYSTEM MODALS (ADD, EDIT, ADJUST, REPORTS) --}}
    {{-- ========================================== --}}

    {{-- ITEM USAGE REPORT MODAL --}}
    <div class="modal fade" id="itemUsageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bolder text-dark"><i class="bi bi-graph-up text-info me-2"></i>Generate Item Usage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light rounded-4 p-4 mt-3 border border-secondary-subtle">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold text-uppercase">Month</label>
                            <select id="usageMonth" class="form-select input-modern">
                                @for($m=1; $m<=12; ++$m)
                                    <option value="{{ sprintf('%02d', $m) }}" {{ date('m') == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold text-uppercase">Year</label>
                            <input type="number" id="usageYear" class="form-control input-modern" value="{{ date('Y') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-3">
                    <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info text-white btn-modern shadow-sm fw-bold" onclick="printUsageReport()">
                        <i class="bi bi-printer-fill me-2"></i> Print Usage
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MONTHLY REPORT MODAL --}}
    <div class="modal fade" id="monthlyReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bolder text-dark"><i class="bi bi-calendar-check text-secondary me-2"></i>Generate Monthly Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light rounded-4 p-4 mt-3 border border-secondary-subtle">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold text-uppercase">Month</label>
                            <select id="reportMonth" class="form-select input-modern">
                                @for($m=1; $m<=12; ++$m)
                                    <option value="{{ sprintf('%02d', $m) }}" {{ date('m') == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold text-uppercase">Year</label>
                            <input type="number" id="reportYear" class="form-control input-modern" value="{{ date('Y') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold text-uppercase">Category Filter</label>
                            <select id="reportCategory" class="form-select input-modern">
                                <option value="ALL">All Categories</option>
                                @foreach($masterCategories as $cat)
                                    <option value="{{ $cat->name }}">{{ strtoupper($cat->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-3">
                    <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-secondary text-white btn-modern shadow-sm fw-bold" onclick="printMonthlyReport()">
                        <i class="bi bi-printer-fill me-2"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ADD NEW ITEM MODAL --}}
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bento-card p-2 border-0 bg-body">
                <form action="/add" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-dark"><i class="bi bi-plus-circle text-success me-2"></i>Add New Supply</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-body-tertiary rounded-4 p-4 mt-3 border border-secondary-subtle">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold text-uppercase">Item Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control input-modern" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold text-uppercase">Category</label>
                                <select class="form-select input-modern" name="category" required>
                                    <option value="" disabled selected>Select Category...</option>
                                    @foreach($masterCategories as $cat)
                                        <option value="{{ $cat->name }}">{{ strtoupper($cat->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Initial Qty <span class="text-danger">*</span></label>
                                <input type="number" class="form-control input-modern" name="quantity" required min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Unit (e.g., pcs)</label>
                                <input type="text" class="form-control input-modern" name="unit" value="pcs">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Price (₱)</label>
                                <input type="number" step="0.01" class="form-control input-modern" name="unit_price">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Reorder Alert</label>
                                <input type="number" class="form-control input-modern border-danger border-opacity-50" name="reorder_level" value="10" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">RIS Number / Ref</label>
                                <input type="text" class="form-control input-modern" name="ris_number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Supplier</label>
                                <input type="text" class="form-control input-modern" name="supplier" placeholder="e.g. Medtek">
                            </div>
                        </div>
                    </div>
                    <div class="modal-header border-0 pt-3 justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-modern shadow-sm"><i class="bi bi-save2-fill me-1"></i> Save to Inventory</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- DYNAMIC MODALS (Looping for each item) --}}
    @foreach($supplies as $item)
        
        {{-- EDIT ITEM MODAL --}}
        <div class="modal fade" id="editModal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bento-card p-2 border-0 bg-body">
                    <form action="/inventory/update/{{ $item->id }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header border-0 pb-0">
                            <h5 class="fw-bolder text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Item Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body bg-body-tertiary rounded-4 p-4 mt-3 border border-secondary-subtle">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Item Name</label>
                                    <input type="text" class="form-control input-modern" name="name" value="{{ $item->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Category</label>
                                    <select class="form-select input-modern" name="category" required>
                                        <option value="" disabled>Select Category...</option>
                                        @foreach($masterCategories as $cat)
                                            <option value="{{ $cat->name }}" {{ $item->category == $cat->name ? 'selected' : '' }}>
                                                {{ strtoupper($cat->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Quantity</label>
                                    <input type="number" class="form-control input-modern fw-bolder text-primary" name="quantity" value="{{ $item->quantity }}" required min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Unit</label>
                                    <input type="text" class="form-control input-modern" name="unit" value="{{ $item->unit }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Price (₱)</label>
                                    <input type="number" step="0.01" class="form-control input-modern" name="unit_price" value="{{ $item->unit_price }}">
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase text-danger">Reorder Alert Level</label>
                                    <input type="number" class="form-control input-modern border-danger border-opacity-50" name="reorder_level" value="{{ $item->reorder_level }}" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Supplier</label>
                                    <input type="text" class="form-control input-modern" name="supplier" value="{{ $item->supplier }}" placeholder="Supplier Name">
                                </div>
                            </div>
                        </div>
                        <div class="modal-header border-0 pt-3 justify-content-end gap-2">
                            <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-modern shadow-sm"><i class="bi bi-save2-fill me-1"></i> Update Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ADJUST STOCK MODAL --}}
        <div class="modal fade" id="adjustModal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bento-card p-2 border-0 bg-body">
                    <form action="/inventory/adjust/{{ $item->id }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="fw-bolder text-dark"><i class="bi bi-sliders text-warning me-2"></i>Adjust Inventory Stock</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body bg-body-tertiary rounded-4 p-4 mt-3 border border-secondary-subtle">
                            <p class="text-muted small mb-3">Use this form to manually correct physical count discrepancies for <strong>{{ $item->name }}</strong>.</p>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Current System Quantity</label>
                                <input type="number" class="form-control input-modern" value="{{ $item->quantity }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Adjustment Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control input-modern fw-bolder text-warning" name="adjustment" required placeholder="e.g. 50 (to add) or -20 (to deduct)">
                                <div class="form-text small">Use a negative number to deduct stock.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label text-muted small fw-bold text-uppercase">Remarks / Reason <span class="text-danger">*</span></label>
                                <input type="text" class="form-control input-modern" name="remarks" required placeholder="e.g. Physical inventory correction">
                            </div>
                        </div>
                        <div class="modal-header border-0 pt-3 justify-content-end gap-2">
                            <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning btn-modern shadow-sm text-dark fw-bold"><i class="bi bi-check2-circle me-1"></i> Apply Adjustment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    @endforeach

    <x-slot name="scripts">
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

        <script>
            // 1. SKELETON LOADERS
            document.addEventListener('DOMContentLoaded', () => {
                const rows = document.querySelectorAll('.inv-row td');
                rows.forEach(cell => cell.classList.add('skeleton'));
                setTimeout(() => rows.forEach(cell => cell.classList.remove('skeleton')), 450);
            });

            // 2. APEXCHARTS: CATEGORY DONUT
            document.addEventListener('DOMContentLoaded', function () {
                const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                
                var options = {
                    series: {!! json_encode($categoryData->values()) !!},
                    labels: {!! json_encode($categoryData->keys()) !!},
                    chart: { type: 'donut', height: 160, background: 'transparent' },
                    theme: { mode: isDark ? 'dark' : 'light' },
                    stroke: { show: true, colors: isDark ? '#1e293b' : '#ffffff', width: 2 },
                    dataLabels: { enabled: false },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '75%',
                                labels: {
                                    show: true,
                                    name: { show: false },
                                    value: {
                                        show: true,
                                        fontSize: '24px',
                                        fontWeight: 800,
                                        color: isDark ? '#f8fafc' : '#0f172a',
                                        formatter: function (val) { return val; }
                                    },
                                    total: {
                                        show: true,
                                        showAlways: true,
                                        label: 'Total',
                                        color: isDark ? '#f8fafc' : '#0f172a',
                                        formatter: function (w) {
                                            return w.globals.seriesTotals.reduce((a, b) => { return a + b }, 0)
                                        }
                                    }
                                }
                            }
                        }
                    },
                    legend: { show: false }
                };
                new ApexCharts(document.querySelector("#categoryDonutChart"), options).render();
            });

            // 3. BULLETPROOF DUAL-FILTER (Search + Category)
            let searchTimeout = null;

            function filterInventory() {
                let textInput = document.getElementById('inventorySearch').value.toLowerCase().trim();
                let categoryInput = document.getElementById('categoryFilter').value.toLowerCase().trim();
                
                if (searchTimeout) clearTimeout(searchTimeout);

                searchTimeout = setTimeout(() => {
                    let rows = document.querySelectorAll('.inv-row');
                    
                    rows.forEach(row => {
                        let textContent = row.innerText.toLowerCase();
                        
                        // Safely grab the category tag we added to the <tr>
                        let rawCat = row.getAttribute('data-category');
                        let rowCategory = rawCat ? rawCat.toLowerCase().trim() : 'uncategorized';
                        
                        // Check if row passes BOTH filters
                        let matchesText = textContent.includes(textInput);
                        let matchesCategory = (categoryInput === 'all' || rowCategory === categoryInput);

                        if (matchesText && matchesCategory) {
                            if (row.style.display === 'none') {
                                row.style.display = '';
                                let cells = row.querySelectorAll('td');
                                cells.forEach(c => c.classList.add('skeleton'));
                                setTimeout(() => cells.forEach(c => c.classList.remove('skeleton')), 300);
                            }
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }, 150); // Fast 150ms debounce
            }
            
            // 4. REPORT PRINTING LOGIC
            function printMonthlyReport() {
                let month = document.getElementById('reportMonth').value;
                let year = document.getElementById('reportYear').value;
                let category = document.getElementById('reportCategory').value;
                
                bootstrap.Modal.getInstance(document.getElementById('monthlyReportModal')).hide();
                printDirectly(`/print-inventory?month=${month}&year=${year}&category=${encodeURIComponent(category)}`);
            }

            function printUsageReport() {
                let month = document.getElementById('usageMonth').value;
                let year = document.getElementById('usageYear').value;
                
                bootstrap.Modal.getInstance(document.getElementById('itemUsageModal')).hide();
                printDirectly(`/item-usage?month=${month}&year=${year}`);
            }

            // 5. SILENT PRINTING LOGIC (Hidden Iframe)
            function printDirectly(url) {
                // Find or create a hidden iframe
                let printFrame = document.getElementById('hiddenPrintFrame') || document.createElement('iframe');
                if(!printFrame.id) {
                    printFrame.id = 'hiddenPrintFrame';
                    printFrame.style.cssText = 'width:0; height:0; border:none; position:absolute; z-index:-1;';
                    document.body.appendChild(printFrame);
                }
                
                // Load the URL into the hidden iframe
                printFrame.src = url;
                
                // Wait for it to load, then trigger the print dialog
                printFrame.onload = () => { 
                    printFrame.contentWindow.focus(); 
                    printFrame.contentWindow.print(); 
                };
            }
        </script>
    </x-slot>
</x-layouts.admin>