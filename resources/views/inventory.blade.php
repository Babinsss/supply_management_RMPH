<x-layouts.admin title="Inventory | Supply Hub">

    <div class="bento-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bolder mb-0"><i class="bi bi-box-seam-fill text-primary me-2"></i> Stock Repository</h5>
            
            <div class="d-flex gap-2">
                <a href="/export-inventory" class="btn btn-modern text-white shadow-sm" style="background-color: #107c41;">
                    <i class="bi bi-file-earmark-excel me-2"></i> Export CSV
                </a>
                
                <button class="btn btn-primary btn-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-lg me-2"></i> Add New Item
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-clean table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Item Details</th>
                        <th>Category</th>
                        <th class="text-center">Stock Level</th>
                        <th class="text-end">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark fs-6">{{ $item->name }}</div>
                            <div class="text-muted-soft small">{{ $item->description }}</div>
                            @if($item->supplier)
                                <div class="text-muted-soft small mt-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-truck me-1"></i> {{ $item->supplier }} 
                                    @if($item->date_delivered) &bull; Delivered: {{ \Carbon\Carbon::parse($item->date_delivered)->format('M d, Y') }} @endif
                                </div>
                            @endif
                        </td>
                        <td><span class="badge bg-light text-secondary border px-2 py-1">{{ $item->category }}</span></td>
                        <td class="text-center">
                            <span class="fw-bold fs-5 {{ $item->quantity <= $item->reorder_level ? 'text-danger' : 'text-success' }}">{{ $item->quantity }}</span>
                            <small class="text-muted-soft">{{ $item->unit }}</small>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <form action="/update/{{ $item->id }}" method="POST" class="d-inline-flex align-items-center bg-light rounded-pill border overflow-hidden p-0 me-2" style="width: 110px;">
                                    @csrf
                                    <input type="number" class="form-control border-0 bg-transparent text-center px-1 fw-bold" name="adjustment" placeholder="± Qty" required>
                                    <button class="btn btn-light text-primary border-start py-1" type="submit"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <button class="btn btn-sm btn-light text-primary border shadow-sm rounded-circle" onclick="printDirectly('/stockcard/{{ $item->id }}')" title="Stockcard">
                                    <i class="bi bi-card-list"></i>
                                </button>
                                <a href="/delete/{{ $item->id }}" class="btn btn-sm btn-light text-danger border shadow-sm rounded-circle" onclick="return confirm('Delete item?')" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted-soft">No inventory items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Item Modal --}}
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2">
                <form action="/add" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder">Register New Supply</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted-soft small fw-bold text-uppercase">Item Name</label>
                            <input type="text" class="input-modern" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted-soft small fw-bold text-uppercase">Category</label>
                            <input type="text" class="input-modern" name="category">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted-soft small fw-bold text-uppercase">Description</label>
                            <input type="text" class="input-modern" name="description">
                        </div>
                        
                        {{-- NEW FIELDS: Supplier & Date Delivered --}}
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label text-muted-soft small fw-bold text-uppercase">Supplier Name</label>
                                <input type="text" class="input-modern" name="supplier" placeholder="e.g. Office Warehouse">
                            </div>
                            <div class="col-5">
                                <label class="form-label text-muted-soft small fw-bold text-uppercase">Date Delivered</label>
                                <input type="date" class="input-modern" name="date_delivered">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted-soft small fw-bold text-uppercase">Initial Qty</label>
                                <input type="number" class="input-modern" name="quantity" value="0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted-soft small fw-bold text-uppercase">Unit</label>
                                <input type="text" class="input-modern" name="unit" required>
                            </div>
                        </div>
                        <input type="hidden" name="reorder_level" value="10">
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                printFrame.onload = () => { printFrame.contentWindow.focus(); printFrame.contentWindow.print(); };
            }
        </script>
    </x-slot>

</x-layouts.admin>