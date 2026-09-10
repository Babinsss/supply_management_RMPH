<x-layouts.admin title="Petty Cash Ledger | Supply Hub">
    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-wallet2 text-success me-2"></i> Petty Cash Ledger
        </h5>
        
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

    <div class="bento-card mb-5">
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-clean align-middle mb-0">
                <thead class="sticky-top bg-white">
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Description / Purpose</th>
                        <th>OR / Ref #</th>
                        <th>Processed By</th>
                        <th>Amount</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                    <tr>
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
                                <br><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-0 mt-1" style="font-size: 0.65rem;">
                                    <i class="bi bi-box-seam me-1"></i> Added to Inventory: {{ $txn->supply->name }}
                                </span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $txn->receipt_reference ?: '-' }}</td>
                        <td><span class="text-muted small fw-bold"><i class="bi bi-person-circle me-1"></i>{{ $txn->processor->name ?? 'System' }}</span></td>
                        <td class="fw-bolder text-danger">
                            -₱{{ number_format($txn->amount, 2) }}
                        </td>
                        <td class="text-end">
                            <button type="button" onclick="printDirectly('/petty-cash/print/{{ $txn->id }}')" class="btn btn-sm btn-light btn-modern border shadow-sm text-primary" title="Print Voucher">
                                <i class="bi bi-printer-fill"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted fw-medium">No petty cash requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- EXPENSE MODAL --}}
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/petty-cash" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-danger"><i class="bi bi-cart-dash-fill me-2"></i> Record Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Price (₱) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control input-modern" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Purpose / Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control input-modern" name="description" placeholder="e.g. Emergency purchase of Cat6 cable" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">OR Number / Receipt <span class="fw-normal text-lowercase">(Optional)</span></label>
                            <input type="text" class="form-control input-modern" name="receipt_reference" placeholder="e.g. OR-123456">
                        </div>
                        
                        <hr class="border-light my-3">
                        <p class="text-dark fw-bold mb-2 small"><i class="bi bi-box-seam text-primary me-1"></i> Inventory Integration <span class="text-danger">*</span></p>
                        
                        {{-- TOGGLE RADIO BUTTONS (Strict Compliance) --}}
                        <div class="mb-3 d-flex flex-wrap gap-3">
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
                        <div id="qtyField" class="mb-2">
                            <input type="number" class="form-control input-modern" name="quantity" placeholder="Quantity Bought" min="1" required>
                        </div>

                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-modern shadow-sm">Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            function toggleInvMode() {
                let mode = document.querySelector('input[name="inventory_mode"]:checked').value;
                document.getElementById('existingFields').style.display = (mode === 'existing') ? 'block' : 'none';
                document.getElementById('newFields').style.display = (mode === 'new') ? 'block' : 'none';
            }

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