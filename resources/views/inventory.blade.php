<x-layouts.admin title="Inventory | Supply Hub">
    
    {{-- Top Action Bar with Search & Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark text-nowrap"><i class="bi bi-box-seam-fill text-primary me-2"></i> Inventory Directory</h5>
        
        <div class="d-flex gap-2 align-items-center justify-content-end flex-grow-1 ms-4">
            <div class="input-group flex-grow-1" style="max-width: 450px;">
                <span class="input-group-text bg-light border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control input-modern border-start-0 rounded-end-4 pl-0" placeholder="Search item, description, supplier, or RIS..." onkeyup="filterInventory()">
            </div>
            
            <a href="/petty-cash" class="btn btn-outline-success btn-modern text-nowrap shadow-sm border">
                <i class="bi bi-wallet2 me-1"></i> Petty Cash
            </a>
            
            <button type="button" class="btn btn-outline-secondary btn-modern text-nowrap shadow-sm border" data-bs-toggle="modal" data-bs-target="#monthlyReportModal">
                <i class="bi bi-calendar-check me-1"></i> Monthly Report
            </button>

            <button type="button" onclick="printDirectly('/print-inventory')" class="btn btn-outline-dark btn-modern text-nowrap shadow-sm border">
                <i class="bi bi-printer-fill me-1"></i> Print Directory
            </button>

            <button class="btn btn-primary btn-modern text-nowrap shadow-sm" data-bs-toggle="modal" data-bs-target="#addSupplyModal">
                <i class="bi bi-plus-lg me-1"></i> New Item
            </button>
        </div>
    </div>

    {{-- Category & Supplier Filters --}}
    <div class="d-flex justify-content-between flex-wrap gap-3 mb-4 align-items-center">
        
        <div class="d-flex flex-wrap gap-2">
            @php 
                $activeCategory = session('active_category', $categories->first()->name ?? 'UNCATEGORIZED');
            @endphp

            @foreach($categories as $cat)
                <button class="btn btn-sm rounded-pill px-3 fw-bold filter-btn {{ $activeCategory === $cat->name ? 'btn-primary shadow-sm' : 'btn-outline-primary' }}" 
                        onclick="setCategory('{{ $cat->name }}', this)">
                    {{ ucwords(strtolower($cat->name)) }}
                </button>
            @endforeach
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small fw-bold text-uppercase text-nowrap"><i class="bi bi-funnel-fill me-1"></i> Supplier:</span>
            <select id="supplierFilter" class="form-select input-modern py-1 px-3 shadow-sm" style="border-radius: 20px; min-width: 220px;" onchange="filterInventory()">
                <option value="ALL">All Suppliers</option>
                @php 
                    $uniqueSuppliers = collect($supplies)->pluck('supplier')->filter()->unique()->sort(); 
                @endphp
                @foreach($uniqueSuppliers as $supplier)
                    <option value="{{ $supplier }}">{{ strtoupper($supplier) }}</option>
                @endforeach
            </select>
        </div>
        
    </div>

    {{-- Main Inventory Table --}}
    <div class="bento-card mb-4">
        <div class="table-responsive">
            <table class="table table-clean mb-0" id="inventoryTable">
                <thead>
                    <tr>
                        <th>Item & Details</th>
                        <th>Current Stock</th>
                        <th>RIS Number</th>
                        <th>Date Added</th>
                        <th class="text-end" style="min-width: 260px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplies as $item)
                        <tr class="inventory-row" id="item-row-{{ $item->id }}" data-category="{{ $item->category ?: 'UNCATEGORIZED' }}" data-supplier="{{ $item->supplier }}">
                            <td>
                                <div class="fw-bold text-dark fs-6 item-name">{{ $item->name }}</div>
                                
                                <div class="mb-2 mt-1">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-0 me-1" style="font-size: 0.65rem;">
                                        {{ $item->category ?: 'UNCATEGORIZED' }}
                                    </span>
                                    @if($item->description)
                                        <span class="text-muted text-uppercase item-desc" style="font-size: 0.70rem; letter-spacing: 0.5px;">
                                            • {{ $item->description }}
                                        </span>
                                    @endif
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-3 mt-1">
                                    @if($item->unit_price)
                                        <span class="text-success fw-bold" style="font-size: 0.75rem;">
                                            <i class="bi bi-tag-fill me-1"></i>₱{{ number_format($item->unit_price, 2) }}
                                        </span>
                                    @endif

                                    @if($item->supplier)
                                        <span class="text-muted-soft item-supplier" style="font-size: 0.75rem;"><i class="bi bi-truck me-1"></i>{{ $item->supplier }}</span>
                                    @endif
                                    
                                    @if($item->date_delivered)
                                        <span class="text-muted-soft" style="font-size: 0.75rem;"><i class="bi bi-calendar-check me-1"></i>Delivered: {{ \Carbon\Carbon::parse($item->date_delivered)->format('M d, Y') }}</span>
                                    @endif

                                    @if($item->expiry_date)
                                        @php 
                                            $daysToExpiry = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($item->expiry_date), false); 
                                        @endphp
                                        
                                        @if($daysToExpiry < 0)
                                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger rounded-pill px-2 py-1">
                                                <i class="bi bi-exclamation-circle-fill"></i> Expired
                                            </span>
                                        @elseif($daysToExpiry <= 60)
                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning rounded-pill px-2 py-1">
                                                <i class="bi bi-clock-history"></i> Expires in {{ floor($daysToExpiry) }} days
                                            </span>
                                        @else
                                            <span class="text-muted-soft" style="font-size: 0.75rem;">
                                                <i class="bi bi-calendar-check me-1"></i>Exp: {{ \Carbon\Carbon::parse($item->expiry_date)->format('M d, Y') }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            
                            {{-- ID tags added for live AJAX syncing --}}
                            <td>
                                <span class="fw-bold fs-5 stock-qty" id="qty-{{ $item->id }}" style="transition: color 0.5s ease;">{{ $item->quantity }}</span> {{ $item->unit ?? 'Units' }}
                                
                                <span id="badge-{{ $item->id }}">
                                    @if($item->quantity == 0)
                                        <span class="badge bg-danger rounded-pill ms-2"><i class="bi bi-x-octagon-fill"></i> Out of Stock</span>
                                    @elseif($item->quantity <= $item->reorder_level)
                                        <span class="badge bg-warning text-dark rounded-pill ms-2"><i class="bi bi-exclamation-triangle-fill"></i> Low Stock</span>
                                    @endif
                                </span>
                            </td>
                            
                            <td>
                                @if($item->ris_number)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 item-ris">
                                        <i class="bi bi-hash"></i> {{ $item->ris_number }}
                                    </span>
                                @else
                                    <span class="text-muted-soft small fst-italic">Without RIS</span>
                                @endif
                            </td>

                            <td>
                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">
                                    {{ $item->created_at->format('M d, Y') }}
                                </div>
                                <div class="text-muted small">
                                    {{ $item->created_at->format('h:i A') }}
                                </div>
                            </td>
                            
                            <td class="text-end text-nowrap">
                                <form action="/inventory/toggle-visibility/{{ $item->id }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-modern border shadow-sm me-1 {{ $item->is_visible ? 'btn-success text-white' : 'btn-secondary text-white' }}" title="{{ $item->is_visible ? 'Visible in Portal' : 'Hidden from Portal' }}">
                                        <i class="bi {{ $item->is_visible ? 'bi-eye-fill' : 'bi-eye-slash-fill' }}"></i>
                                    </button>
                                </form>
                                
                                {{-- SUPERADMIN ADJUSTMENT BUTTON (Hidden from normal admins) --}}
                                @if(auth()->check() && auth()->user()->role === 'superadmin')
                                    <button type="button" class="btn btn-sm btn-light btn-modern text-warning border shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#adjustModal-{{ $item->id }}" title="Superadmin Stock Adjustment">
                                        <i class="bi bi-sliders"></i>
                                    </button>
                                @endif

                                <button type="button" class="btn btn-sm btn-light btn-modern text-success border shadow-sm me-1" onclick="printDirectly('/bincard/{{ $item->id }}')" title="Bin Card">
                                    <i class="bi bi-card-list"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-light btn-modern text-info border shadow-sm me-1" onclick="printDirectly('/rapc/{{ $item->id }}')" title="Report on Actual Physical Count">
                                    <i class="bi bi-file-earmark-spreadsheet"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-light btn-modern text-dark border shadow-sm me-1" onclick="printDirectly('/stockcard/{{ $item->id }}')" title="Stockcard">
                                    <i class="bi bi-printer"></i>
                                </button>

                                <button class="btn btn-sm btn-light btn-modern text-primary border shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#editModal-{{ $item->id }}" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                
                                <button class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $item->id }}" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted-soft fw-medium">No inventory items found. Add some supplies to get started!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <nav aria-label="Inventory Pagination" class="mb-5">
        <ul class="pagination justify-content-end" id="paginationControls"></ul>
    </nav>

    {{-- Monthly Report Modal --}}
    <div class="modal fade" id="monthlyReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-3 border-0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bolder text-dark">Generate Monthly Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold text-uppercase">Month</label>
                            <select id="reportMonth" class="form-select input-modern">
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08" selected>August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold text-uppercase">Year</label>
                            <input type="number" id="reportYear" class="input-modern" value="{{ date('Y') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold text-uppercase">Category Filter</label>
                            <select id="reportCategory" class="form-select input-modern">
                                <option value="ALL">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->name }}">{{ strtoupper($cat->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-modern shadow-sm" onclick="printMonthlyReport()">
                        <i class="bi bi-printer-fill me-2"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Add New Item Modal --}}
    <div class="modal fade text-start" id="addSupplyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/add" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-dark">Register New Supply</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label text-muted small fw-bold text-uppercase">Item Name <span class="text-danger">*</span></label>
                                <input type="text" class="input-modern" name="name" placeholder="e.g. EPSON 664" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold text-uppercase">Description / Type</label>
                                <input type="text" class="input-modern" name="description" placeholder="e.g. Printer Ink - Black">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Initial Stock <span class="text-danger">*</span></label>
                                <input type="number" class="input-modern" name="quantity" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Unit Price (₱)</label>
                                <input type="number" step="0.01" class="input-modern" name="unit_price" placeholder="0.00">
                            </div>
                            <div class="col-12"><hr class="my-2 border-light"></div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Supplier</label>
                                <input type="text" class="input-modern" name="supplier" placeholder="e.g. Zuellig Pharma">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Date Delivered</label>
                                <input type="date" class="input-modern" name="date_delivered">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Expiry Date</label>
                                <input type="date" class="input-modern" name="expiry_date">
                            </div>
                            
                            <select class="form-select input-modern" name="category" required>
                                <option value="" disabled selected>Select Category...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->name }}">{{ strtoupper($cat->name) }}</option>
                                @endforeach
                            </select>
                            
                            <div class="col-12">
                                <label class="form-label text-muted small fw-bold text-uppercase">RIS Number <span class="fw-normal text-lowercase">(Optional)</span></label>
                                <input type="text" class="input-modern" name="ris_number" placeholder="e.g. RIS-2026-07-001">
                            </div>
                        </div>
                    </div>
                    <div class="modal-header border-0 pt-0 justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern shadow-sm"><i class="bi bi-plus-lg me-2"></i> Register Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit, Delete, & Adjustment Modals Loop --}}
    @foreach($supplies as $item)
        <div class="modal fade text-start" id="editModal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bento-card p-2 border-0">
                    <form action="/inventory/update/{{ $item->id }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header border-0 pb-0">
                            <h5 class="fw-bolder text-dark">Edit Item Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Item Name</label>
                                    <input type="text" class="input-modern" name="name" value="{{ $item->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Description / Type</label>
                                    <input type="text" class="input-modern" name="description" value="{{ $item->description }}">
                                </div>
                                
                                {{-- EDIT FIX: Stock Quantity field is now strictly readonly --}}
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Stock Quantity</label>
                                    <input type="number" class="input-modern bg-light text-muted" name="quantity" value="{{ $item->quantity }}" min="0" readonly required title="Quantity can only be updated via the Petty Cash Ledger or Requisitions">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Unit Price (₱)</label>
                                    <input type="number" step="0.01" class="input-modern" name="unit_price" value="{{ $item->unit_price }}">
                                </div>
                                <div class="col-12"><hr class="my-2 border-light"></div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Supplier</label>
                                    <input type="text" class="input-modern" name="supplier" value="{{ $item->supplier }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Date Delivered</label>
                                    <input type="date" class="input-modern" name="date_delivered" value="{{ $item->date_delivered }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Expiry Date</label>
                                    <input type="date" class="input-modern" name="expiry_date" value="{{ $item->expiry_date }}">
                                </div>
                                
                                <div class="col-md-5">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Category <span class="text-danger">*</span></label>
                                    <select class="form-select input-modern" name="category" required>
                                        <option value="" disabled>Select Category...</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->name }}" {{ $item->category == $cat->name ? 'selected' : '' }}>
                                                {{ strtoupper($cat->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label text-muted small fw-bold text-uppercase">RIS Number <span class="fw-normal text-lowercase">(Optional)</span></label>
                                    <input type="text" class="input-modern" name="ris_number" value="{{ $item->ris_number }}">
                                </div>
                            </div>
                        </div>
                        <div class="modal-header border-0 pt-0 justify-content-end gap-2">
                            <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-modern shadow-sm"><i class="bi bi-save me-2"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bento-card p-3 border-0">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <p class="mb-0 text-dark">Are you sure you want to permanently delete <strong>{{ $item->name }}</strong>?</p>
                        <p class="small text-muted mt-2 mb-0">This will remove it from the inventory directory. This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <a href="/delete/{{ $item->id }}" class="btn btn-danger btn-modern shadow-sm">
                            <i class="bi bi-trash-fill me-1"></i> Delete Item
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- SUPERADMIN ADJUSTMENT MODAL (Hidden from normal admins) --}}
        @if(auth()->check() && auth()->user()->role === 'superadmin')
        <div class="modal fade text-start" id="adjustModal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bento-card p-2 border-0">
                    <form action="/superadmin/inventory/adjust/{{ $item->id }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="fw-bolder text-warning"><i class="bi bi-sliders me-2"></i> Stock Adjustment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="alert alert-warning border-0 rounded-3 py-2 small fw-bold shadow-sm mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Current Stock: {{ $item->quantity }}
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Action <span class="text-danger">*</span></label>
                                <select class="form-select input-modern fw-bold" name="action" required>
                                    <option value="subtract" class="text-danger">Deduct Stock (-)</option>
                                    <option value="add" class="text-success">Add Stock (+)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Quantity to Adjust <span class="text-danger">*</span></label>
                                <input type="number" class="form-control input-modern" name="quantity" min="1" required>
                            </div>

                            <div class="mb-2">
                                <label class="form-label text-muted small fw-bold text-uppercase">Reason for Correction <span class="text-danger">*</span></label>
                                <select class="form-select input-modern" name="reason" required>
                                    <option value="" disabled selected>Select reason...</option>
                                    <option value="PHYSICAL COUNT DISCREPANCY">Physical Count Discrepancy</option>
                                    <option value="DATA ENTRY ERROR">Data Entry Error (Correction)</option>
                                    <option value="DAMAGED / EXPIRED">Damaged / Expired Item</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning btn-modern shadow-sm text-dark fw-bolder"><i class="bi bi-check2-circle me-1"></i> Confirm Adjustment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endforeach

    <x-slot name="scripts">
        <script>
            function printDirectly(url) {
                let printFrame = document.getElementById('hiddenPrintFrame') || document.createElement('iframe');
                if(!printFrame.id) {
                    printFrame.id = 'hiddenPrintFrame';
                    printFrame.style.cssText = 'width:0; height:0; border:none; position:absolute;';
                    document.body.appendChild(printFrame);
                }
                printFrame.src = url;
                printFrame.onload = () => { 
                    printFrame.contentWindow.focus(); 
                    printFrame.contentWindow.print(); 
                };
            }

            function printMonthlyReport() {
                let month = document.getElementById('reportMonth').value;
                let year = document.getElementById('reportYear').value;
                let category = document.getElementById('reportCategory').value;
                let url = `/print-inventory?month=${month}&year=${year}&category=${encodeURIComponent(category)}`;
                
                let modalElement = document.getElementById('monthlyReportModal');
                let modalInstance = bootstrap.Modal.getInstance(modalElement);
                if(modalInstance) modalInstance.hide();
                printDirectly(url);
            }

            let currentPage = 1;
            const rowsPerPage = 10; 
            
            // Set the active category directly from Laravel session data
            let currentCategory = '{!! session("active_category", $categories->first()->name ?? "") !!}';

            function setCategory(cat, btnElement) {
                currentCategory = cat;
                currentPage = 1; 

                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('btn-primary', 'shadow-sm');
                    btn.classList.add('btn-outline-primary');
                });
                btnElement.classList.remove('btn-outline-primary');
                btnElement.classList.add('btn-primary', 'shadow-sm');

                applyFiltersAndPagination();
            }

            function filterInventory() {
                currentPage = 1; 
                applyFiltersAndPagination();
            }

            function applyFiltersAndPagination() {
                let searchText = document.getElementById('searchInput').value.toLowerCase();
                let currentSupplier = document.getElementById('supplierFilter').value;
                
                let allRows = document.querySelectorAll('.inventory-row');
                let visibleRows = [];

                allRows.forEach(row => {
                    let rowCategory = row.getAttribute('data-category');
                    let rowSupplier = row.getAttribute('data-supplier') || '';
                    let rowText = row.innerText.toLowerCase();
                    
                    let matchesSearch = rowText.includes(searchText);
                    let matchesCategory = (currentCategory === '' || rowCategory === currentCategory);
                    let matchesSupplier = (currentSupplier === 'ALL' || rowSupplier === currentSupplier);

                    // Combine all three conditions
                    if (matchesSearch && matchesCategory && matchesSupplier) {
                        visibleRows.push(row);
                    }
                    row.style.display = 'none'; 
                });

                let totalPages = Math.ceil(visibleRows.length / rowsPerPage) || 1;
                if (currentPage > totalPages) currentPage = totalPages;

                let startIndex = (currentPage - 1) * rowsPerPage;
                let endIndex = startIndex + rowsPerPage;

                visibleRows.forEach((row, index) => {
                    if (index >= startIndex && index < endIndex) {
                        row.style.display = '';
                    }
                });

                renderPagination(totalPages);
            }

            function changePage(page) {
                currentPage = page;
                applyFiltersAndPagination();
                window.scrollTo({ top: 0, behavior: 'smooth' }); 
            }

            function renderPagination(totalPages) {
                let container = document.getElementById('paginationControls');
                let html = '';

                if (totalPages > 1) {
                    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                                <button class="page-link shadow-sm" onclick="changePage(${currentPage - 1})">Previous</button>
                             </li>`;
                    
                    for (let i = 1; i <= totalPages; i++) {
                        html += `<li class="page-item ${currentPage === i ? 'active' : ''}">
                                    <button class="page-link shadow-sm" onclick="changePage(${i})">${i}</button>
                                 </li>`;
                    }

                    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                                <button class="page-link shadow-sm" onclick="changePage(${currentPage + 1})">Next</button>
                             </li>`;
                }

                container.innerHTML = html;
            }

            // --- LIVE INVENTORY SYNC (EVERY 10 SECONDS) ---
            setInterval(() => {
                fetch('/api/inventory/live')
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
                            let qtyElement = document.getElementById(`qty-${item.id}`);
                            let badgeElement = document.getElementById(`badge-${item.id}`);
                            
                            if(qtyElement) {
                                // If quantity changed, update it and flash blue
                                if(qtyElement.innerText != item.quantity) {
                                    qtyElement.innerText = item.quantity;
                                    qtyElement.style.color = '#0d6efd'; // Bootstrap Primary Blue
                                    
                                    // Reset color back to normal after 1.5 seconds
                                    setTimeout(() => { qtyElement.style.color = ''; }, 1500);
                                }

                                // Update warning badges dynamically
                                if(item.quantity == 0) {
                                    badgeElement.innerHTML = `<span class="badge bg-danger rounded-pill ms-2"><i class="bi bi-x-octagon-fill"></i> Out of Stock</span>`;
                                } else if(item.quantity <= item.reorder_level) {
                                    badgeElement.innerHTML = `<span class="badge bg-warning text-dark rounded-pill ms-2"><i class="bi bi-exclamation-triangle-fill"></i> Low Stock</span>`;
                                } else {
                                    badgeElement.innerHTML = '';
                                }
                            }
                        });
                    })
                    .catch(error => console.error('Error syncing live inventory:', error));
            }, 10000); // 10,000 milliseconds = 10 seconds

            document.addEventListener('DOMContentLoaded', () => {
                applyFiltersAndPagination();
            });
        </script>
    </x-slot>

</x-layouts.admin>