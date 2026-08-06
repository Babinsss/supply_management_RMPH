<x-layouts.admin title="Inventory | Supply Hub">

    {{-- Top Action Bar with Search & Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark"><i class="bi bi-box-seam-fill text-primary me-2"></i> Inventory Directory</h5>
        
        <div class="d-flex gap-2 align-items-center" style="width: 60%;">
            {{-- Search Bar --}}
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control input-modern border-start-0 rounded-end-4 pl-0" placeholder="Search item, description, supplier, or RIS..." onkeyup="filterInventory()">
            </div>
            
            {{-- Monthly Report Modal Trigger --}}
            <button type="button" class="btn btn-outline-secondary btn-modern bg-white text-nowrap shadow-sm border" data-bs-toggle="modal" data-bs-target="#monthlyReportModal">
                <i class="bi bi-calendar-check me-1"></i> Monthly Report
            </button>

            {{-- Print Report Button (General) --}}
            <button type="button" onclick="printDirectly('/print-inventory')" class="btn btn-outline-dark btn-modern bg-white text-nowrap shadow-sm border">
                <i class="bi bi-printer-fill me-1"></i> Print Directory
            </button>

            {{-- Add New Item Button --}}
            <button class="btn btn-primary btn-modern text-nowrap shadow-sm" data-bs-toggle="modal" data-bs-target="#addSupplyModal">
                <i class="bi bi-plus-lg me-1"></i> New Item
            </button>
        </div>
    </div>

    {{-- Category Segregation Filter Buttons --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <button class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm filter-btn" onclick="setCategory('ALL', this)">All Items</button>
        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold filter-btn" onclick="setCategory('OFFICE SUPPLIES', this)">Office Supplies</button>
        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold filter-btn" onclick="setCategory('MEDICAL SUPPLIES', this)">Medical Supplies</button>
        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold filter-btn" onclick="setCategory('JANITORIAL SUPPLIES', this)">Janitorial Supplies</button>
        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold filter-btn" onclick="setCategory('MEDICAL EQUIPMENT', this)">Medical Equipment</button>
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
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Flat loop for clean pagination --}}
                    @forelse($supplies as $item)
                        <tr class="inventory-row" data-category="{{ $item->category ?: 'UNCATEGORIZED' }}">
                            {{-- Item Name, Badge, Description, Supplier, Delivery, Expiry & PRICE --}}
                            <td>
                                <div class="fw-bold text-dark fs-6 item-name">{{ $item->name }}</div>
                                
                                {{-- Category Badge & Description --}}
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

                                {{-- Supplier, Delivery, Expiry & Price Info Group --}}
                                <div class="d-flex flex-wrap align-items-center gap-3 mt-1">
                                    {{-- Unit Price Display --}}
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

                                    {{-- Smart Alert: Expiry Logic --}}
                                    @if($item->expiry_date)
                                        @php 
                                            // Calculate days until expiry
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
                            
                            {{-- Smart Alert: Stock Quantity --}}
                            <td>
                                <span class="fw-bold fs-5">{{ $item->quantity }}</span> {{ $item->unit ?? 'Units' }}
                                
                                @if($item->quantity == 0)
                                    <span class="badge bg-danger rounded-pill ms-2"><i class="bi bi-x-octagon-fill"></i> Out of Stock</span>
                                @elseif($item->quantity <= $item->reorder_level)
                                    <span class="badge bg-warning text-dark rounded-pill ms-2"><i class="bi bi-exclamation-triangle-fill"></i> Low Stock</span>
                                @endif
                            </td>
                            
                            {{-- RIS Number Column --}}
                            <td>
                                @if($item->ris_number)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 item-ris">
                                        <i class="bi bi-hash"></i> {{ $item->ris_number }}
                                    </span>
                                @else
                                    <span class="text-muted-soft small fst-italic">Without RIS</span>
                                @endif
                            </td>

                            {{-- Date Added Column --}}
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">
                                    {{ $item->created_at->format('M d, Y') }}
                                </div>
                                <div class="text-muted small">
                                    {{ $item->created_at->format('h:i A') }}
                                </div>
                            </td>
                            
                            {{-- Actions (Visibility, Stockcard, Edit & Delete) --}}
                            <td class="text-end text-nowrap">
                                
                                {{-- NEW: Visibility Toggle Button --}}
                                <form action="/inventory/toggle-visibility/{{ $item->id }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-modern border shadow-sm me-1 {{ $item->is_visible ? 'btn-success text-white' : 'btn-secondary text-white' }}" title="{{ $item->is_visible ? 'Visible in Portal' : 'Hidden from Portal' }}">
                                        <i class="bi {{ $item->is_visible ? 'bi-eye-fill' : 'bi-eye-slash-fill' }}"></i>
                                    </button>
                                </form>

                                <button type="button" class="btn btn-sm btn-light btn-modern text-dark border shadow-sm me-1" onclick="printDirectly('/stockcard/{{ $item->id }}')">
                                    <i class="bi bi-printer"></i> Stockcard
                                </button>
                                <button class="btn btn-sm btn-light btn-modern text-primary border shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#editModal-{{ $item->id }}">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                
                                {{-- Delete Button --}}
                                <button class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $item->id }}">
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

    {{-- Pagination Controls Container --}}
    <nav aria-label="Inventory Pagination" class="mb-5">
        <ul class="pagination justify-content-end" id="paginationControls">
            {{-- Injected dynamically via Javascript --}}
        </ul>
    </nav>

    {{-- OUTSIDE ARCHITECTURE: Monthly Report Modal --}}
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
                                <option value="OFFICE SUPPLIES">OFFICE SUPPLIES</option>
                                <option value="MEDICAL SUPPLIES">MEDICAL SUPPLIES</option>
                                <option value="JANITORIAL SUPPLIES">JANITORIAL SUPPLIES</option>
                                <option value="MEDICAL EQUIPMENT">MEDICAL EQUIPMENT</option>
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

    {{-- OUTSIDE ARCHITECTURE: Add New Item Modal --}}
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
                            
                            <div class="col-12">
                                <hr class="my-2 border-light">
                            </div>

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
                            
                            {{-- Category Dropdown --}}
                            <div class="col-md-5">
                                <label class="form-label text-muted small fw-bold text-uppercase">Category <span class="text-danger">*</span></label>
                                <select class="form-select input-modern" name="category" required>
                                    <option value="" disabled selected>Select Category...</option>
                                    <option value="OFFICE SUPPLIES">OFFICE SUPPLIES</option>
                                    <option value="MEDICAL SUPPLIES">MEDICAL SUPPLIES</option>
                                    <option value="JANITORIAL SUPPLIES">JANITORIAL SUPPLIES</option>
                                    <option value="MEDICAL EQUIPMENT">MEDICAL EQUIPMENT</option>
                                </select>
                            </div>
                            
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

    {{-- OUTSIDE ARCHITECTURE: Edit & Delete Modals Loop --}}
    @foreach($supplies as $item)
        
        {{-- Edit Modal --}}
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
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Stock Quantity</label>
                                    <input type="number" class="input-modern" name="quantity" value="{{ $item->quantity }}" min="0" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Unit Price (₱)</label>
                                    <input type="number" step="0.01" class="input-modern" name="unit_price" value="{{ $item->unit_price }}">
                                </div>
                                
                                <div class="col-12">
                                    <hr class="my-2 border-light">
                                </div>
                                
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
                                
                                {{-- Edit Category Dropdown --}}
                                <div class="col-md-5">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Category <span class="text-danger">*</span></label>
                                    <select class="form-select input-modern" name="category" required>
                                        <option value="OFFICE SUPPLIES" {{ $item->category == 'OFFICE SUPPLIES' ? 'selected' : '' }}>OFFICE SUPPLIES</option>
                                        <option value="MEDICAL SUPPLIES" {{ $item->category == 'MEDICAL SUPPLIES' ? 'selected' : '' }}>MEDICAL SUPPLIES</option>
                                        <option value="JANITORIAL SUPPLIES" {{ $item->category == 'JANITORIAL SUPPLIES' ? 'selected' : '' }}>JANITORIAL SUPPLIES</option>
                                        <option value="MEDICAL EQUIPMENT" {{ $item->category == 'MEDICAL EQUIPMENT' ? 'selected' : '' }}>MEDICAL EQUIPMENT</option>
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

        {{-- NEW: Delete Confirmation Modal (FIXED: Switched to GET link) --}}
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
                        {{-- FIXED: Changed form to anchor link --}}
                        <a href="/delete/{{ $item->id }}" class="btn btn-danger btn-modern shadow-sm">
                            <i class="bi bi-trash-fill me-1"></i> Delete Item
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    @endforeach

    {{-- Javascript for Filters, Pagination, & Printing --}}
    <x-slot name="scripts">
        <script>
            // --- SEAMLESS PRINTING ---
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

            // --- MONTHLY REPORT LOGIC ---
            function printMonthlyReport() {
                let month = document.getElementById('reportMonth').value;
                let year = document.getElementById('reportYear').value;
                let category = document.getElementById('reportCategory').value;
                
                // Build the URL with the selected filters
                let url = `/print-inventory?month=${month}&year=${year}&category=${encodeURIComponent(category)}`;
                
                // Close the modal cleanly
                let modalElement = document.getElementById('monthlyReportModal');
                let modalInstance = bootstrap.Modal.getInstance(modalElement);
                if(modalInstance) {
                    modalInstance.hide();
                }
                
                // Send to the seamless print function
                printDirectly(url);
            }

            // --- FILTERING & PAGINATION LOGIC ---
            let currentPage = 1;
            const rowsPerPage = 10; // Change this number to show more/less items per page
            let currentCategory = 'ALL';

            // Runs when a category pill button is clicked
            function setCategory(cat, btnElement) {
                currentCategory = cat;
                currentPage = 1; // Reset to page 1 on new filter

                // Update button styles to show active state
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('btn-primary', 'shadow-sm');
                    btn.classList.add('btn-outline-primary');
                });
                btnElement.classList.remove('btn-outline-primary');
                btnElement.classList.add('btn-primary', 'shadow-sm');

                applyFiltersAndPagination();
            }

            // Runs when the search bar is typed in
            function filterInventory() {
                currentPage = 1; // Reset to page 1 on new search
                applyFiltersAndPagination();
            }

            // Core function to handle what rows are visible
            function applyFiltersAndPagination() {
                let searchText = document.getElementById('searchInput').value.toLowerCase();
                let allRows = document.querySelectorAll('.inventory-row');
                let visibleRows = [];

                // 1. Filter the rows by Search + Category
                allRows.forEach(row => {
                    let rowCategory = row.getAttribute('data-category');
                    let rowText = row.innerText.toLowerCase();
                    
                    let matchesSearch = rowText.includes(searchText);
                    let matchesCategory = (currentCategory === 'ALL' || rowCategory === currentCategory);

                    if (matchesSearch && matchesCategory) {
                        visibleRows.push(row);
                    }
                    row.style.display = 'none'; // Hide all initially
                });

                // 2. Calculate Pagination
                let totalPages = Math.ceil(visibleRows.length / rowsPerPage) || 1;
                if (currentPage > totalPages) currentPage = totalPages;

                let startIndex = (currentPage - 1) * rowsPerPage;
                let endIndex = startIndex + rowsPerPage;

                // 3. Show only the rows for the current page
                visibleRows.forEach((row, index) => {
                    if (index >= startIndex && index < endIndex) {
                        row.style.display = '';
                    }
                });

                // 4. Update the Pagination HTML at the bottom
                renderPagination(totalPages);
            }

            // Runs when a pagination number is clicked
            function changePage(page) {
                currentPage = page;
                applyFiltersAndPagination();
                window.scrollTo({ top: 0, behavior: 'smooth' }); // Optional: scrolls to top on page change
            }

            // Builds the Bootstrap Pagination Buttons
            function renderPagination(totalPages) {
                let container = document.getElementById('paginationControls');
                let html = '';

                // Only show pagination if there is more than 1 page
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

            // Initialize the table properly as soon as the page loads
            document.addEventListener('DOMContentLoaded', () => {
                applyFiltersAndPagination();
            });
        </script>
    </x-slot>

</x-layouts.admin>