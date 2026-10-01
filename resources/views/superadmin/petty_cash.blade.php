<x-layouts.admin title="Petty Cash Ledger | Supply Hub">
    
    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <div>
            <h5 class="fw-bolder mb-0 text-dark">
                <i class="bi bi-wallet2 text-success me-2"></i> Petty Cash Ledger
            </h5>
            <p class="text-muted small mb-0 mt-1">Track financial disbursements and inventory acquisitions.</p>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-danger btn-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <i class="bi bi-cart-dash-fill me-1"></i> Record Expense
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm rounded-4 border-0 mb-4 py-3">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i> 
            <span class="fw-bold">{{ session('success') }}</span>
        </div>
    @endif

    <!-- NEW: DYNAMIC FINANCIAL SUMMARY CARDS -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="bento-card bg-success bg-opacity-10 border border-success border-opacity-25 d-flex justify-content-between align-items-center h-100">
                <div>
                    <p class="text-success fw-bold mb-1 text-uppercase small">Ledger Transactions</p>
                    <h2 class="fw-bolder mb-0 text-success">{{ count($transactions) }} <span class="fs-6 fw-normal text-success opacity-75">Entries</span></h2>
                </div>
                <i class="bi bi-receipt fs-1 text-success opacity-50"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bento-card bg-danger bg-opacity-10 border border-danger border-opacity-25 d-flex justify-content-between align-items-center h-100">
                <div>
                    <p class="text-danger fw-bold mb-1 text-uppercase small">Total Disbursed (Page)</p>
                    <h2 class="fw-bolder mb-0 text-danger">₱{{ number_format(collect($transactions)->sum('amount'), 2) }}</h2>
                </div>
                <i class="bi bi-cash-stack fs-1 text-danger opacity-50"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bento-card bg-body-tertiary border border-secondary-subtle d-flex justify-content-between align-items-center h-100">
                <div>
                    <p class="text-muted fw-bold mb-1 text-uppercase small">Last Updated</p>
                    <h4 class="fw-bolder mb-0 text-dark">
                        {{ collect($transactions)->first() ? collect($transactions)->first()->created_at->format('M d, Y') : 'N/A' }}
                    </h4>
                </div>
                <i class="bi bi-clock-history fs-1 text-muted opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- UPGRADED TABLE SECTION -->
    <div class="bento-card mb-5">
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-clean align-middle mb-0">
                <thead class="sticky-top bg-body z-1">
                    <tr>
                        <th class="bg-body">Date & Time</th>
                        <th class="bg-body">Type</th>
                        <th class="bg-body">Description / Purpose</th>
                        <th class="bg-body">OR / Ref #</th>
                        <th class="bg-body">Processed By</th>
                        <th class="bg-body">Amount</th>
                        <th class="bg-body text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                    <tr class="expense-row">
                        <td>
                            <div class="fw-bold text-dark">{{ $txn->created_at->format('M d, Y') }}</div>
                            <div class="text-muted small">{{ $txn->created_at->format('h:i A') }}</div>
                        </td>
                        <td>
                            <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-1">Expense</span>
                        </td>
                        <td>
                            <span class="fw-bold text-dark">{{ $txn->description }}</span>
                            @if($txn->supply_id)
                                <br><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-0 mt-1" style="font-size: 0.65rem;">
                                    <!-- FIXED: Added null-safe operator ?-> and a fallback for deleted items -->
                                    <i class="bi bi-box-seam me-1"></i> Added to Inventory: {{ $txn->supply?->name ?? 'Deleted/Archived Item' }}
                                </span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $txn->receipt_reference ?: '-' }}</td>
                        <!-- FIXED: Added null-safe operator ?-> for the processor as well, just in case a user gets deleted -->
                        <td><span class="text-muted small fw-bold"><i class="bi bi-person-circle me-1"></i>{{ $txn->processor?->name ?? 'System' }}</span></td>
                        <td class="fw-bolder text-danger">
                            -₱{{ number_format($txn->amount, 2) }}
                        </td>
                        <td class="text-end">
                            <button type="button" onclick="printDirectly('/petty-cash/print/{{ $txn->id }}')" class="btn btn-sm btn-body btn-modern border border-secondary-subtle shadow-sm text-primary" title="Print Voucher">
                                <i class="bi bi-printer-fill"></i> Print
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted fw-medium">
                            <i class="bi bi-wallet fs-1 d-block mb-3 opacity-25"></i>
                            No petty cash requests yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- EXPENSE MODAL (Dark Mode Optimized) --}}
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0 bg-body">
                <form action="/petty-cash" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-danger"><i class="bi bi-cart-dash-fill me-2"></i> Record Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        
                        <div class="bg-body-tertiary p-3 rounded-4 mb-4 border border-secondary-subtle">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Price (₱) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control input-modern fw-bolder text-danger" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Purpose / Description <span class="text-danger">*</span></label>
                                <input type="text" class="form-control input-modern" name="description" placeholder="e.g. Emergency purchase of IT supplies" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label text-muted small fw-bold text-uppercase">OR Number / Receipt <span class="fw-normal text-lowercase">(Optional)</span></label>
                                <input type="text" class="form-control input-modern" name="receipt_reference" placeholder="e.g. OR-123456">
                            </div>
                        </div>
                        
                        <p class="text-dark fw-bold mb-2 small"><i class="bi bi-box-seam text-primary me-1"></i> Inventory Integration <span class="text-danger">*</span></p>
                        
                        <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle">
                            {{-- TOGGLE RADIO BUTTONS --}}
                            <div class="mb-3 d-flex flex-wrap gap-3 border-bottom border-secondary-subtle pb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="inventory_mode" id="modeExisting" value="existing" checked onchange="toggleInvMode()">
                                    <label class="form-check-label small fw-bold text-primary" for="modeExisting">Add to Existing Item</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="inventory_mode" id="modeNew" value="new" onchange="toggleInvMode()">
                                    <label class="form-check-label small fw-bold text-success" for="modeNew">Create New Item</label>
                                </div>
                            </div>

                            {{-- EXISTING ITEM DROPDOWN --}}
                            <div id="existingFields" class="mb-2">
                                <select class="form-select input-modern" name="supply_id">
                                    <option value="" disabled selected>Select existing item...</option>
                                    @foreach($supplies as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- NEW ITEM FIELDS --}}
                            <div id="newFields" style="display: none;" class="mb-2">
                                <input type="text" class="form-control input-modern mb-2" name="new_item_name" placeholder="Item Name (e.g. SSD 500GB)">
                                <select class="form-select input-modern" name="new_item_category">
                                    <option value="" disabled selected>Select Category...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->name }}">{{ strtoupper($cat->name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- SHARED QUANTITY FIELD --}}
                            <div id="qtyField" class="mt-2">
                                <input type="number" class="form-control input-modern fw-bolder text-primary" name="quantity" placeholder="Quantity Acquired" min="1" required>
                            </div>
                        </div>

                    </div>
                    <div class="modal-header border-0 pt-0 justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-modern shadow-sm"><i class="bi bi-save2-fill me-1"></i> Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            // SKELETON LOADERS
            document.addEventListener('DOMContentLoaded', () => {
                const rows = document.querySelectorAll('.expense-row td');
                rows.forEach(cell => cell.classList.add('skeleton'));
                setTimeout(() => {
                    rows.forEach(cell => cell.classList.remove('skeleton'));
                }, 400);
            });

            // MODAL LOGIC
            function toggleInvMode() {
                let mode = document.querySelector('input[name="inventory_mode"]:checked').value;
                document.getElementById('existingFields').style.display = (mode === 'existing') ? 'block' : 'none';
                document.getElementById('newFields').style.display = (mode === 'new') ? 'block' : 'none';
            }

            // PRINTING LOGIC
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
        </script>
    </x-slot>
</x-layouts.admin>