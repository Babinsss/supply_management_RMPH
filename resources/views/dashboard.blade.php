<x-layouts.admin title="Dashboard | Supply Hub">
    
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="bento-card bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-white-50 fw-bold mb-1 text-uppercase small">Pending Requests</p>
                    <h1 class="fw-bolder mb-0 display-4" id="navPendingCount">{{ $pending_count }}</h1>
                </div>
                <i class="bi bi-bell-fill fs-1 text-white-50"></i>
            </div>
        </div>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bolder mb-0"><i class="bi bi-inbox-fill text-warning me-2"></i> Department Requisitions Monitor</h5>
            
            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-light border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="requestSearchInput" class="form-control input-modern border-start-0 rounded-end-4 pl-0" placeholder="Search department, item, or status..." onkeyup="filterRequests()">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-clean mb-0">
                <thead>
                    <tr>
                        <th>Date & Time (PH)</th>
                        <th>Requestor</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $batch)
                    <tr class="request-row">
                        <td>
                            @php $batchDate = $batch['created_at'] ?? $batch['items']->first()->created_at; @endphp
                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($batchDate)->timezone('Asia/Manila')->format('M d, Y') }}</div>
                            <div class="text-muted-soft small">{{ \Carbon\Carbon::parse($batchDate)->timezone('Asia/Manila')->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $batch['department_name'] }}</div>
                            <div class="text-muted-soft small">{{ $batch['requested_by'] }}</div>
                        </td>
                        <td>
                            @foreach($batch['items'] as $req)
                                <div class="mb-2">
                                    <div class="small fw-medium">
                                        <span class="text-primary fw-bolder">{{ $req->quantity }}x</span> {{ $req->supply->name }}
                                    </div>
                                    @if($req->supply->category)
                                        <div class="text-muted-soft text-uppercase" style="font-size: 0.65rem; margin-left: 1.4rem; letter-spacing: 0.5px;">
                                            <i class="bi bi-tag"></i> {{ $req->supply->category }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td>
                            @if($batch['status'] == 'Pending')
                                <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2">
                                    <i class="bi bi-hourglass-split me-1"></i> Pending
                                </span>
                            @elseif($batch['status'] == 'Approved')
                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2">
                                    <i class="bi bi-check-circle-fill me-1"></i> Approved
                                </span>
                            @elseif($batch['status'] == 'Denied')
                                <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-2">
                                    <i class="bi bi-x-circle-fill me-1"></i> Disapproved
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            {{-- Transferred Buttons --}}
                            @if($batch['status'] == 'Pending')
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="/process-batch/{{ $batch['batch_id'] }}/deny" class="btn btn-sm btn-modern btn-outline-danger" onclick="return confirm('Are you sure you want to disapprove this entire request?')">
                                        <i class="bi bi-x-circle me-1"></i> Disapprove
                                    </a>
                                    <button type="button" class="btn btn-sm btn-modern btn-success" data-bs-toggle="modal" data-bs-target="#approveModal-{{ $batch['batch_id'] }}">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Issue
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted-soft fw-medium">No requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Transferred Review Modals --}}
    @foreach($requests as $batch)
        @if($batch['status'] == 'Pending')
            <div class="modal fade text-start" id="approveModal-{{ $batch['batch_id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bento-card p-2 border-0">
                        <form action="/process-batch/{{ $batch['batch_id'] }}/approve" method="POST">
                            @csrf
                            <div class="modal-header border-0 pb-0">
                                <h5 class="fw-bolder text-dark">Issue Request</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted-soft small mb-4">Adjust quantities before issuing. You cannot exceed stock.</p>
                                @foreach($batch['items'] as $req)
                                <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-3 rounded-4 border border-white border-2 shadow-sm">
                                    <div class="pe-2">
                                        <div class="fw-bold text-dark fs-6">{{ $req->supply->name }}</div>
                                        @if($req->supply->category)
                                            <div class="text-muted-soft text-uppercase mt-1 mb-1" style="font-size: 0.70rem; letter-spacing: 0.5px;">
                                                {{ $req->supply->category }}
                                            </div>
                                        @endif
                                        <div class="text-muted-soft small mt-1">Requested: <span class="fw-bold">{{ $req->quantity }}</span> | Stock: <span class="fw-bold">{{ $req->supply->quantity }}</span></div>
                                    </div>
                                    <div style="width: 90px; flex-shrink: 0;">
                                        <input type="number" class="input-modern text-center py-2 fw-bolder text-primary" name="qty_{{ $req->id }}" 
                                               value="{{ min($req->quantity, $req->supply->quantity) }}" 
                                               min="0" max="{{ min($req->quantity, $req->supply->quantity) }}" required>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="modal-header border-0 pt-0 justify-content-end gap-2">
                                <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success btn-modern shadow-sm"><i class="bi bi-check-circle-fill me-2"></i> Approve & Issue</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <x-slot name="scripts">
        <script>
            let currentCount = parseInt("{{ $pending_count }}") || 0;
            setInterval(() => {
                fetch('/api/pending-count')
                    .then(r => r.json())
                    .then(data => {
                        if (data.count !== currentCount && !document.querySelector('.modal.show')) { 
                            location.reload(); 
                        }
                    });
            }, 5000);

            function filterRequests() {
                let input = document.getElementById('requestSearchInput').value.toLowerCase();
                let rows = document.querySelectorAll('.request-row');
                
                rows.forEach(row => {
                    let textContent = row.innerText.toLowerCase();
                    if (textContent.includes(input)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        </script>
    </x-slot>
</x-layouts.admin>