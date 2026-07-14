<x-layouts.admin title="Inventory | Supply Hub">

    {{-- Top Action Bar with Search --}}
    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark"><i class="bi bi-box-seam-fill text-primary me-2"></i> Inventory Directory</h5>
        
        <div class="d-flex gap-3 align-items-center" style="width: 50%;">
            {{-- Search Bar --}}
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control input-modern border-start-0 rounded-end-4 pl-0" placeholder="Search item, description, supplier, or RIS..." onkeyup="filterInventory()">
            </div>
            
            {{-- Add New Item Button --}}
            <button class="btn btn-primary btn-modern text-nowrap shadow-sm" data-bs-toggle="modal" data-bs-target="#addSupplyModal">
                <i class="bi bi-plus-lg me-1"></i> New Item
            </button>
        </div>
    </div>

    {{-- Main Inventory Table --}}
    <div class="bento-card mb-5">
        <div class="table-responsive">
            <table class="table table-clean mb-0" id="inventoryTable">
                <thead>
                    <tr>
                        <th>Item & Details</th>
                        <th>Current Stock</th>
                        <th>RIS Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplies as $item)
                    <tr class="inventory-row">
                        {{-- Item Name, Description, Supplier, Delivery, Expiry & PRICE --}}
                        <td>
                            <div class="fw-bold text-dark fs-6 item-name">{{ $item->name }}</div>
                            
                            @if($item->description)
                                <div class="text-muted text-uppercase item-desc mb-2" style="font-size: 0.70rem; letter-spacing: 0.5px;">
                                    {{ $item->description }}
                                </div>
                            @endif

                            {{-- Supplier, Delivery, Expiry & Price Info Group --}}
                            <div class="d-flex flex-wrap gap-3 mt-1">
                                {{-- NEW: Unit Price Display --}}
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
                                    <span class="text-danger fw-bold" style="font-size: 0.75rem;"><i class="bi bi-calendar-x me-1"></i>Exp: {{ \Carbon\Carbon::parse($item->expiry_date)->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </td>
                        
                        {{-- Stock Quantity --}}
                        <td>
                            <span class="badge {{ $item->quantity > 0 ? 'bg-success text-success' : 'bg-danger text-danger' }} bg-opacity-10 rounded-pill px-3 py-2 border {{ $item->quantity > 0 ? 'border-success' : 'border-danger' }} border-opacity-25">
                                {{ $item->quantity }} Units
                            </span>
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
                        
                        {{-- Actions (Stockcard & Edit) --}}
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-light btn-modern text-dark border shadow-sm me-1" onclick="printDirectly('/stockcard/{{ $item->id }}')">
                                <i class="bi bi-printer"></i> Stockcard
                            </button>
                            <button class="btn btn-sm btn-light btn-modern text-primary border shadow-sm" data-bs-toggle="modal" data-bs-target="#editModal-{{ $item->id }}">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted-soft fw-medium">No inventory items found. Add some supplies to get started!</td></tr>
                    @endforelse
                </tbody>
            </table>
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

                            {{-- Replaced col-12 with an 8/4 split for RIS and Category --}}
                            <div class="col-md-8">
                                <label class="form-label text-muted small fw-bold text-uppercase">RIS Number <span class="fw-normal text-lowercase">(Optional)</span></label>
                                <input type="text" class="input-modern" name="ris_number" placeholder="e.g. RIS-2026-07-001">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Category</label>
                                <input type="text" class="input-modern" name="category" placeholder="e.g. IT Equipment">
                            </div>
                            
                            {{-- Added Hidden Inputs Required by Controller --}}
                            <input type="hidden" name="unit" value="pcs">
                            <input type="hidden" name="reorder_level" value="10">
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

    {{-- OUTSIDE ARCHITECTURE: Edit Modals Loop --}}
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

                                {{-- Replaced col-12 with an 8/4 split for RIS and Category --}}
                                <div class="col-md-8">
                                    <label class="form-label text-muted small fw-bold text-uppercase">RIS Number <span class="fw-normal text-lowercase">(Optional)</span></label>
                                    <input type="text" class="input-modern" name="ris_number" value="{{ $item->ris_number }}">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Category</label>
                                    <input type="text" class="input-modern" name="category" value="{{ $item->category }}" placeholder="e.g. Office Supplies">
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
    @endforeach

    {{-- Javascript for Real-Time Search & Seamless Printing --}}
    <x-slot name="scripts">
        <script>
            // Seamless Hidden Iframe Print Function
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

            // Inventory Search Filter
            function filterInventory() {
                let input = document.getElementById('searchInput').value.toLowerCase();
                let rows = document.querySelectorAll('.inventory-row');
                
                rows.forEach(row => {
                    let name = row.querySelector('.item-name') ? row.querySelector('.item-name').innerText.toLowerCase() : '';
                    let desc = row.querySelector('.item-desc') ? row.querySelector('.item-desc').innerText.toLowerCase() : '';
                    let ris = row.querySelector('.item-ris') ? row.querySelector('.item-ris').innerText.toLowerCase() : '';
                    let supplier = row.querySelector('.item-supplier') ? row.querySelector('.item-supplier').innerText.toLowerCase() : '';
                    
                    if (name.includes(input) || desc.includes(input) || ris.includes(input) || supplier.includes(input)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        </script>
    </x-slot>

</x-layouts.admin>