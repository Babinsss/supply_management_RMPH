<x-layouts.admin title="Dashboard | Supply Hub">
    
    <div class="row g-4 mb-4">
        {{-- Quick Stat: Pending --}}
        <div class="col-md-6">
            <div class="bento-card bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-white-50 fw-bold mb-1 text-uppercase small">Pending Requests</p>
                    <h1 class="fw-bolder mb-0 display-4" id="navPendingCount">{{ $pending_count }}</h1>
                </div>
                <i class="bi bi-bell-fill fs-1 text-white-50"></i>
            </div>
        </div>
        {{-- Quick Stat: Processed --}}
        <div class="col-md-6">
            <div class="bento-card d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted-soft fw-bold mb-1 text-uppercase small">Total Requisitions Processed</p>
                    <h1 class="fw-bolder mb-0 display-4 text-dark">{{ count($requests) }}</h1>
                </div>
                <i class="bi bi-check2-all fs-1 text-primary opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="bento-card mb-5">
        <h5 class="fw-bolder mb-4"><i class="bi bi-inbox-fill text-warning me-2"></i> Action Required: Department Requisitions</h5>
        
        <div class="table-responsive">
            <table class="table table-clean mb-0">
                <thead><tr><th>Requestor</th><th>Items</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($requests as $batch)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $batch['department_name'] }}</div>
                            <div class="text-muted-soft small">{{ $batch['requested_by'] }}</div>
                        </td>
                        <td>
                            @foreach($batch['items'] as $req)
                                <div class="small fw-medium"><span class="text-primary">{{ $req->quantity }}x</span> {{ $req->supply->name }}</div>
                            @endforeach
                        </td>
                        <td>
                            @if($batch['status'] == 'Pending')
                                <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2">Pending</span>
                            @else
                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2">Processed</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($batch['status'] == 'Pending')
                                {{-- CLEAN TRIGER: Keeps table rows lightweight and collision-free --}}
                                <button type="button" class="btn btn-sm btn-modern btn-success" data-bs-toggle="modal" data-bs-target="#approveModal-{{ $batch['batch_id'] }}">
                                    Approve
                                </button>
                                <a href="/process-batch/{{ $batch['batch_id'] }}/deny" class="btn btn-sm btn-modern btn-light text-danger"><i class="bi bi-x-lg"></i></a>
                            @else
                                <button type="button" class="btn btn-sm btn-modern btn-light text-muted" onclick="printDirectly('/print-bulk/{{ $batch['batch_id'] }}')">
                                    <i class="bi bi-printer-fill"></i> RIS
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted-soft fw-medium">All caught up! No pending requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- OUTSIDE ARCHITECTURE: Modals are isolated here at the layout base layer to ensure maximum performance --}}
    @foreach($requests as $batch)
        @if($batch['status'] == 'Pending')
            <div class="modal fade text-start" id="approveModal-{{ $batch['batch_id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bento-card p-2 border-0">
                        <form action="/process-batch/{{ $batch['batch_id'] }}/approve" method="POST">
                            @csrf
                            <div class="modal-header border-0 pb-0">
                                <h5 class="fw-bolder text-dark">Review & Dispense</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted-soft small mb-4">Adjust the quantities below to limit the dispensed amount. You cannot exceed the requested amount or your current active stock.</p>
                                
                                @foreach($batch['items'] as $req)
                                <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-3 rounded-4 border border-white border-2 shadow-sm">
                                    <div class="pe-2">
                                        <div class="fw-bold text-dark fs-6">{{ $req->supply->name }}</div>
                                        <div class="text-muted-soft small">Requested: <span class="fw-bold text-dark">{{ $req->quantity }}</span> | Stock: {{ $req->supply->quantity }}</div>
                                    </div>
                                    <div style="width: 90px; flex-shrink: 0;">
                                        <label class="text-muted-soft small fw-bold text-uppercase" style="font-size: 0.65rem;">Release</label>
                                        <input type="number" class="input-modern text-center (any) py-2 fw-bolder text-primary" name="qty_{{ $req->id }}" 
                                               value="{{ $req->quantity <= $req->supply->quantity ? $req->quantity : $req->supply->quantity }}" 
                                               min="0" 
                                               max="{{ $req->quantity <= $req->supply->quantity ? $req->quantity : $req->supply->quantity }}" 
                                               required>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="modal-header border-0 pt-0 justify-content-end gap-2">
                                <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success btn-modern shadow-sm"><i class="bi bi-check2-circle me-2"></i> Confirm Release</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

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
                printFrame.onload = () => { printFrame.contentWindow.focus(); printFrame.contentWindow.print(); };
            }

            // Upgraded Background Poller
            let count = parseInt("{{ $pending_count }}") || 0;
            setInterval(() => {
                fetch('/api/pending-count')
                    .then(r => r.json())
                    .then(data => {
                        // FIX: Only refresh the dashboard if there isn't an open modal active on screen
                        if (data.count > count && !document.querySelector('.modal.show')) {
                            location.reload(); 
                        }
                    });
            }, 5000);
        </script>
    </x-slot>
</x-layouts.admin>