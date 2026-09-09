<x-layouts.admin title="Petty Cash Ledger | Superadmin">
    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-wallet2 text-success me-2"></i> Petty Cash Ledger
        </h5>
        
        <div class="d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 text-success px-4 py-2 rounded-3 border border-success border-opacity-25">
                <span class="small fw-bold text-uppercase d-block" style="font-size: 0.7rem; letter-spacing: 0.5px;">Current Balance</span>
                <span class="fs-4 fw-bolder">₱{{ number_format($balance, 2) }}</span>
            </div>
            
            <button class="btn btn-outline-success btn-modern shadow-sm border" data-bs-toggle="modal" data-bs-target="#replenishModal">
                <i class="bi bi-plus-circle-fill me-1"></i> Replenish Fund
            </button>
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
                            @if($txn->type == 'Replenishment')
                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-1">Replenishment</span>
                            @else
                                <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-1">Expense</span>
                            @endif
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
                        <td class="fw-bolder {{ $txn->type == 'Replenishment' ? 'text-success' : 'text-danger' }}">
                            {{ $txn->type == 'Replenishment' ? '+' : '-' }}₱{{ number_format($txn->amount, 2) }}
                        </td>
                        <td class="text-end">
                            <button type="button" onclick="printDirectly('/superadmin/petty-cash/print/{{ $txn->id }}')" class="btn btn-sm btn-light btn-modern border shadow-sm text-primary" title="Print Voucher">
                                <i class="bi bi-printer-fill"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted fw-medium">No petty cash transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- EXPENSE MODAL --}}
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/superadmin/petty-cash" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="Expense">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-danger"><i class="bi bi-cart-dash-fill me-2"></i> Record Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Amount Spent (₱) <span class="text-danger">*</span></label>
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
                        <p class="text-dark fw-bold mb-2 small"><i class="bi bi-box-seam text-primary me-1"></i> Add to Inventory? (Optional)</p>
                        <div class="row g-2">
                            <div class="col-8">
                                <select class="form-select input-modern" name="supply_id">
                                    <option value="">Do not link to inventory</option>
                                    @foreach($supplies as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <input type="number" class="form-control input-modern" name="quantity" placeholder="Qty Bought">
                            </div>
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

    {{-- REPLENISH MODAL --}}
    <div class="modal fade" id="replenishModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/superadmin/petty-cash" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="Replenishment">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-success"><i class="bi bi-plus-circle-fill me-2"></i> Replenish Fund</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Amount Added (₱) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control input-modern" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Source / Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control input-modern" name="description" value="Fund Replenishment" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold text-uppercase">Voucher / Check No. <span class="fw-normal text-lowercase">(Optional)</span></label>
                            <input type="text" class="form-control input-modern" name="receipt_reference" placeholder="e.g. CHK-998877">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-modern shadow-sm">Add Funds</button>
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
                printFrame.onload = () => { 
                    printFrame.contentWindow.focus(); 
                    printFrame.contentWindow.print(); 
                };
            }
        </script>
    </x-slot>
</x-layouts.admin>