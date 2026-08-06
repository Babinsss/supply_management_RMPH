<x-layouts.approver title="Inventory Viewer | QMO">

    {{-- Top Action Bar with Search & Print Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark"><i class="bi bi-eye-fill text-primary me-2"></i> Inventory Status</h5>
        
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

    {{-- Main Inventory Table (Read-Only) --}}
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
                            
                            {{-- Actions (Stockcard ONLY for Approver) --}}
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-light btn-modern text-dark border shadow-sm me-1" onclick="printDirectly('/stockcard/{{ $item->id }}')">
                                    <i class="bi bi-printer"></i> Stockcard
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted-soft fw-medium">No inventory items found.</td></tr>
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
                
                let url = `/print-inventory?month=${month}&year=${year}&category=${encodeURIComponent(category)}`;
                
                let modalElement = document.getElementById('monthlyReportModal');
                let modalInstance = bootstrap.Modal.getInstance(modalElement);
                if(modalInstance) {
                    modalInstance.hide();
                }
                
                printDirectly(url);
            }

            // --- FILTERING & PAGINATION LOGIC ---
            let currentPage = 1;
            const rowsPerPage = 10;
            let currentCategory = 'ALL';

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
                let allRows = document.querySelectorAll('.inventory-row');
                let visibleRows = [];

                allRows.forEach(row => {
                    let rowCategory = row.getAttribute('data-category');
                    let rowText = row.innerText.toLowerCase();
                    
                    let matchesSearch = rowText.includes(searchText);
                    let matchesCategory = (currentCategory === 'ALL' || rowCategory === currentCategory);

                    if (matchesSearch && matchesCategory) {
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

            document.addEventListener('DOMContentLoaded', () => {
                applyFiltersAndPagination();
            });
        </script>
    </x-slot>

</x-layouts.approver>